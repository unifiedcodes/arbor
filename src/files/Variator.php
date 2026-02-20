<?php

namespace Arbor\files;


use Arbor\files\contracts\VariantsPolicyInterface;
use Arbor\files\contracts\VariantProfileInterface;
use Arbor\files\FileHydrator;
use Arbor\files\Evaluator;
use Arbor\files\PolicyCatalog;
use Arbor\facades\Storage;
use RuntimeException;
use Arbor\storage\Uri;


class Variator
{
    public function __construct(
        private PolicyCatalog $policyCatalog
    ) {}


    public function generate(string|Uri $uri, ?array $options = []): array
    {
        $uri = Storage::normalizeUri($uri);

        $fileContext = FileHydrator::contextFromUri($uri);

        $policy = $this->policyCatalog->resolvePolicy(
            VariantsPolicyInterface::class,
            $uri->scheme(),
            $fileContext->mime(),
            $options
        );

        if (!$policy instanceof VariantsPolicyInterface) {
            throw new RuntimeException("variant policy must implement VariantPolicyInterface");
        }

        $variants = $this->generateVariants(
            $policy->variants($fileContext)
        );

        return $variants;
    }


    public function generateVariants(array $variantProfiles): array
    {
        $variants = [];

        foreach ($variantProfiles as $profile) {
            $variants[] = $this->createVariant($profile);
        }

        return $variants;
    }


    public function createVariant(VariantProfileInterface $profile)
    {
        print_r($profile);
    }
}
