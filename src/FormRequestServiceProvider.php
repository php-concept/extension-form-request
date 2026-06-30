<?php declare(strict_types=1);

namespace Concept\Extensions\FormRequest;

use Concept\Extensions\Event\Events\ExtensionAwakened;
use Concept\Extensions\Event\Support\EventDispatcherResolver;
use Concept\Extensions\FormRequest\Contracts\FormRequestFactoryInterface;
use Concept\Extensions\FormRequest\Factory\FormRequestFactory;
use League\Container\ServiceProvider\AbstractServiceProvider;

final class FormRequestServiceProvider extends AbstractServiceProvider
{
    private const string EXTENSION_NAME = 'form-request';

    /**
     * @param array<string> $globalExcept
     */
    public function __construct(
        private readonly array $globalExcept = [],
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

            return new FormRequestFactory($container, $this->globalExcept);
        })->setShared(true);
    }
}
