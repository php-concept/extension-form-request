<?php declare(strict_types=1);

namespace Concept\Extensions\FormRequest\Factory;

use Concept\Extensions\CastingValinor\Contracts\CasterInterface;
use Concept\Extensions\FormRequest\Contracts\FormRequestFactoryInterface;
use Concept\Extensions\FormRequest\Contracts\FormRequestInterface;
use Concept\Extensions\ValidationRakit\Contracts\ValidatorInterface;
use Concept\Extensions\ValidationRakit\ValidationLogger;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class FormRequestFactory implements FormRequestFactoryInterface
{
    private const string ERR_CLASS_MUST_IMPLEMENT_INTERFACE = 'Class %s must implement %s.';
    private const string ERR_CASTER_NOT_REGISTERED = 'CasterInterface is not registered in the container.';
    private const string ERR_VALIDATOR_NOT_REGISTERED = 'ValidatorInterface is not registered in the container.';

    /**
     * @param array<string> $globalExcept
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly array $globalExcept = [],
    ) {}

    public function make(string $className, ServerRequestInterface $request): FormRequestInterface
    {
        if (!is_subclass_of($className, FormRequestInterface::class)) {
            throw new InvalidArgumentException(sprintf(
                self::ERR_CLASS_MUST_IMPLEMENT_INTERFACE,
                $className,
                FormRequestInterface::class,
            ));
        }

        $validator = $this->container->get(ValidatorInterface::class);
        if (!$validator instanceof ValidatorInterface) {
            throw new RuntimeException(self::ERR_VALIDATOR_NOT_REGISTERED);
        }

        $caster = $this->container->has(CasterInterface::class)
            ? $this->resolveCaster()
            : null;

        $validationLogger = $this->container->has(ValidationLogger::class)
            ? $this->container->get(ValidationLogger::class)
            : null;

        return new $className($request, $validator, $caster, $validationLogger, $this->globalExcept);
    }

    private function resolveCaster(): CasterInterface
    {
        $caster = $this->container->get(CasterInterface::class);
        if (!$caster instanceof CasterInterface) {
            throw new InvalidArgumentException(self::ERR_CASTER_NOT_REGISTERED);
        }

        return $caster;
    }
}
