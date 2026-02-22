<?php

namespace Arbor\files\utilities;


use Arbor\files\contracts\VariantsPolicyInterface;


/**
 * Abstract base class for file variant policy implementations.
 *
 * Extends {@see BaseFilePolicy} with the {@see VariantsPolicyInterface} contract,
 * scoping the policy specifically to the generation and management of file variants
 * such as image resizes, format conversions, or quality tiers. Concrete subclasses
 * implement the variant-specific logic (e.g. target dimensions, output formats,
 * naming conventions) defined by {@see VariantsPolicyInterface}.
 *
 * @package Arbor\files\utilities
 */
abstract class BaseVariantPolicy extends BaseFilePolicy implements VariantsPolicyInterface {}
