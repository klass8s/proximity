<?php declare(strict_types=1);

namespace Proximity;

use Proximity\MethodsInterception\InterceptionContext;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;

final readonly class ProxyGenerator
{
    public const string DEFAULT_NAMESPACE = 'Proximity\Proxy';

    private const string METHOD_CODE_TEMPLATE = <<<'CODE'
        $methodName = '{METHOD}';
        $_proximityArgs = func_get_args();
        {ARGS_NAMED_MAP}
        foreach ($this->proximityOptions->bodyInterceptorsForMethod($methodName) as $interceptor) {
            $result = $interceptor->beforeMethodBody($this, $this->proximityOriginalObject, $methodName, $_proximityArgsNamedMap);
            if ($result?->preventMethodBody) {
                {RETURN_STATEMENT_BEFORE_BODY}
            }
        }

        $returnValue = parent::{METHOD}(...$_proximityArgs);

        $context = new InterceptionContext($returnValue);
        foreach ($this->proximityOptions->resultInterceptorsForMethod($methodName) as $interceptor) {
            $interceptor->afterMethodBody($this, $this->proximityOriginalObject, $methodName, $_proximityArgsNamedMap, $context);
        }

        {RETURN_STATEMENT_AFTER_BODY}
        CODE;
    private Printer $printer;
    public function __construct(public string $proxyClassesNamespace = self::DEFAULT_NAMESPACE)
    {
        $this->printer = new Printer();
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     */
    public function generate(\ReflectionClass $reflectionClass): GeneratedProxy
    {
        $this->ensureClassCanBeProxied($reflectionClass);

        $namespaceName = $this->proxyClassesNamespace;
        if ($reflectionClass->getNamespaceName()) {
            $namespaceName .= '\\'.$reflectionClass->getNamespaceName();
        }
        $namespace = new PhpNamespace($namespaceName);
        $namespace
            ->addUse(InterceptionContext::class)
            ->addUse(ProxyOptions::class);

        $className = $reflectionClass->getShortName().'Proxy';
        $class = new ClassType($className, $namespace);
        $namespace->add($class);

        $class
            ->setExtends($reflectionClass->getName())
            ->addImplement(ProxyInterface::class)
            ->setReadOnly($reflectionClass->isReadOnly());

        $this->generateProperties($reflectionClass, $class);
        $this->generateConstructor($reflectionClass, $class);
        $this->generateMethods($reflectionClass, $class);

        $fqcn = $namespace->getName().'\\'.$class->getName();
        $code = $this->printer->printNamespace($namespace);

        return new GeneratedProxy($fqcn, $code, $reflectionClass);
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     */
    private function generateMethods(\ReflectionClass $reflectionClass, ClassType $class): void
    {
        foreach ($reflectionClass->getMethods() as $method) {
            if ($method->isConstructor() || $method->isPrivate() || $method->isFinal() || $method->isStatic()) {
                continue;
            }

            $this->generateMethod($method, $class);
        }
    }

    private function generateMethod(\ReflectionMethod $reflectionMethod, ClassType $class): void
    {
        $method = $class->addMethod($reflectionMethod->getName());
        $method
            ->setReturnReference($reflectionMethod->returnsReference())
            ->setVariadic($reflectionMethod->isVariadic());

        if ($reflectionMethod->hasReturnType()) {
            $method->setReturnType((string)$reflectionMethod->getReturnType());
        }

        $argsNamedMapCode = ['$_proximityArgsNamedMap = [];'];
        foreach ($reflectionMethod->getParameters() as $reflectionParam) {
            $param = $method
                ->addParameter($reflectionParam->name)
                ->setReference($reflectionParam->isPassedByReference())
                ->setNullable($reflectionParam->allowsNull());
            if ($reflectionParam->hasType()) {
                $param->setType((string)$reflectionParam->getType());
            }
            if ($reflectionParam->isVariadic()) {
                $method->setVariadic();
            }
            if ($reflectionParam->isDefaultValueAvailable()) {
                $param->setDefaultValue($reflectionParam->getDefaultValue());
            }

            $argsNamedMapCode[] = strtr('$_proximityArgsNamedMap[\'{PARAM}\'] = ${PARAM};', ['{PARAM}' => $reflectionParam->name]);
        }

        $isVoid = 'void' === (string)$reflectionMethod->getReturnType();
        $returnStatementBeforeBody = $isVoid ? 'return;' : 'return $result->returnValue;';
        $returnStatementAfterBody = $isVoid ? 'return;' : 'return $context->returnValue;';

        $code = strtr(self::METHOD_CODE_TEMPLATE, [
            '{METHOD}' => $reflectionMethod->getName(),
            '{RETURN_STATEMENT_BEFORE_BODY}' => $returnStatementBeforeBody,
            '{RETURN_STATEMENT_AFTER_BODY}' => $returnStatementAfterBody,
            '{ARGS_NAMED_MAP}' => implode(PHP_EOL, $argsNamedMapCode),
        ]);

        $method->setBody($code);
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     */
    private function generateConstructor(\ReflectionClass $reflectionClass, ClassType $class): void
    {
        $method = $class->addMethod('__construct');
        $method
            ->addParameter('object')
            ->setType($reflectionClass->getName());
        $method
            ->addParameter('options')
            ->setType(ProxyOptions::class);

        $code = <<<'CODE'
            $this->proximityOriginalObject = $object;
            $this->proximityOptions = $options;

            $privatePropertySetterClosure = function (string $propName) use ($object) {
                $this->$propName = & $object->$propName;
            };
            $readonlyPropertySetterClosure = function (string $propName) use ($object) {
                $this->$propName = $object->$propName;
            };
            $hookedPropertyCarryClosure = function (string $class, string $propName) use ($object): void {
                $property = new \ReflectionProperty($class, $propName);
                if ($property->isInitialized($object)) {
                    $property->setRawValue($this, $property->getRawValue($object));
                }
            };
            CODE;

        foreach (self::allPropertiesFromClassAndParents($reflectionClass) as $property) {
            if ($property->hasHooks()) {
                if ($property->isVirtual()) {
                    // A virtual hooked property has no backing store — the inherited hooks
                    // compute it from other state, so there is nothing to carry over.
                    continue;
                }

                // A backed hooked property cannot be bound by reference (PHP forbids references
                // to hooked properties), so its pre-proxy backing value is copied over once,
                // bypassing the hooks. Later writes to the original object do not reach the
                // proxy — the proxy replaces the original from here on.
                $propertyInitializationCode = <<<'PROPERTY_CODE'
                    $hookedPropertyCarryClosure('{CLASS}', '{PROPERTY}');
                    PROPERTY_CODE;
                $propertyInitializationCode = strtr($propertyInitializationCode, [
                    '{PROPERTY}' => $property->name,
                    '{CLASS}' => $property->getDeclaringClass()->getName(),
                ]);
                $code = $code.PHP_EOL.$propertyInitializationCode;

                continue;
            }

            if ($property->isReadOnly()) {
                $propertyInitializationCode = <<<'PROPERTY_CODE'
                        $readonlyPropertySetterClosure->bindTo($this, '{CLASS}')->__invoke('{PROPERTY}');
                    PROPERTY_CODE;
                $propertyInitializationCode = strtr($propertyInitializationCode, [
                    '{PROPERTY}' => $property->name,
                    '{CLASS}' => $property->getDeclaringClass()->getName(),
                ]);
                $code = $code.PHP_EOL.$propertyInitializationCode;

                continue;
            }

            if ($property->isPrivate()) {
                $propertyInitializationCode = <<<'PROPERTY_CODE'
                    $privatePropertySetterClosure->bindTo($this, '{CLASS}')->__invoke('{PROPERTY}');
                    PROPERTY_CODE;

                $propertyInitializationCode = strtr($propertyInitializationCode, [
                    '{PROPERTY}' => $property->name,
                    '{CLASS}' => $property->getDeclaringClass()->getName(),
                ]);
                $code = $code.PHP_EOL.$propertyInitializationCode;

                continue;
            }

            $code = $code.PHP_EOL.strtr('$this->{PROPERTY} = &$object->{PROPERTY};', ['{PROPERTY}' => $property->name]);
        }

        $method->setBody($code);
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     */
    private function generateProperties(\ReflectionClass $reflectionClass, ClassType $class): void
    {
        $class
            ->addProperty('proximityOriginalObject')
            ->setPrivate()
            ->setType($reflectionClass->getName())
            ->setReadOnly();

        $class
            ->addProperty('proximityOptions')
            ->setPrivate()
            ->setType(ProxyOptions::class)
            ->setReadOnly();
    }

    /**
     * @param \ReflectionClass<object> $class
     */
    private function ensureClassCanBeProxied(\ReflectionClass $class): void
    {
        if ($class->isFinal()) {
            throw new \InvalidArgumentException(
                sprintf('Class "%s" cannot be proxied because it is final', $class->getName())
            );
        }
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     *
     * @return list<\ReflectionProperty>
     */
    private static function allPropertiesFromClassAndParents(\ReflectionClass $reflectionClass): array
    {
        $properties = [];
        do {
            $instanceProps = array_filter($reflectionClass->getProperties(), fn (\ReflectionProperty $prop) => (!$prop->isStatic()) && $reflectionClass->getName() === $prop->getDeclaringClass()->getName());
            $properties = array_merge($properties, $instanceProps);
            $reflectionClass = $reflectionClass->getParentClass();
        } while ($reflectionClass);

        return $properties;
    }
}
