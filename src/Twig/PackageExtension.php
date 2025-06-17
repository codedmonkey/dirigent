<?php

namespace CodedMonkey\Dirigent\Twig;

use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use Composer\Pcre\Preg;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Attribute\AsTwigFunction;
use Twig\Attribute\AsTwigTest;

readonly class PackageExtension
{
    public function __construct(
        private PackageRepository $packageRepository,
        private UrlGeneratorInterface $router,
        private RequestStack $requestStack,
    ) {
    }

    #[AsTwigFunction('packageFilterParameters')]
    public function getFilterParameters(string $parameter, string|int|null $value): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $filters = [
            'query' => $request->query->get('query'),
            'registry' => $request->query->get('registry'),
        ];

        $filters[$parameter] = $value;

        return array_filter($filters, fn ($value) => null !== $value);
    }

    #[AsTwigFunction('packageFilterUrl')]
    public function createFilterUrl(string $parameter, string|int|null $value): string
    {
        return $this->router->generate('dashboard_packages', $this->getFilterParameters($parameter, $value));
    }

    #[AsTwigTest('existing_package')]
    public function packageExistsTest(mixed $package): bool
    {
        if ($package instanceof Package) {
            return null !== $package->getId();
        }

        if (is_string($package)) {
            if (!Preg::isMatch('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $package)) {
                return false;
            }

            return null !== $this->packageRepository->findOneByName($package);
        }

        return false;
    }
}
