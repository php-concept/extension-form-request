<?php declare(strict_types=1);

namespace Concept\Extensions\FormRequest;

use Closure;
use Concept\Extensions\CastingValinor\Contracts\CasterInterface;
use Concept\Extensions\Event\Events\ExtensionAwakened;
use Concept\Extensions\Event\Support\EventDispatcherResolver;
use Concept\Extensions\FormRequest\Contracts\FormRequestFactoryInterface;
use Concept\Extensions\FormRequest\Factory\FormRequestFactory;
use Concept\Extensions\ValidationRakit\Contracts\ValidatorInterface;
use Concept\Extensions\ValidationRakit\ValidationLogger;
use League\Container\ServiceProvider\AbstractServiceProvider;
use RuntimeException;

final class FormRequestServiceProvider extends AbstractServiceProvider
{
    private const string EXTENSION_NAME = 'form-request';
    private const string ERR_VALIDATOR_INVALID = 'Validator factory did not return a ValidatorInterface instance.';

    /**
     * @param array<string> $globalExcept
     * @param Closure(): mixed $validatorFactory
     * @param Closure(): mixed|null $casterFactory
     * @param Closure(): mixed|null $validationLoggerFactory
     */
    public function __construct(
        private readonly Closure $validatorFactory,
        private readonly array $globalExcept = [],
        private readonly ?Closure $casterFactory = null,
        private readonly ?Closure $validationLoggerFactory = null,
    ) {}

    public function provides(string $id): bool
    {
        return $id === FormRequestFactoryInterface::class;
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(FormRequestFactoryInterface::class, function() use ($container): FormRequestFactory {
            EventDispatcherResolver::optional($container)?->dispatch(new ExtensionAwakened(
                extensionName: self::EXTENSION_NAME,
                anchorId: FormRequestFactoryInterface::class,
            ));

            $validator = ($this->validatorFactory)();
            if (!$validator instanceof ValidatorInterface) {
                throw new RuntimeException(self::ERR_VALIDATOR_INVALID);
            }

            $caster = $this->casterFactory !== null ? ($this->casterFactory)() : null;
            $validationLogger = $this->validationLoggerFactory !== null
                ? ($this->validationLoggerFactory)()
                : null;

            return new FormRequestFactory(
                validator: $validator,
                globalExcept: $this->globalExcept,
                caster: $caster instanceof CasterInterface ? $caster : null,
                validationLogger: $validationLogger instanceof ValidationLogger ? $validationLogger : null,
            );
        })->setShared(true);
    }
}
