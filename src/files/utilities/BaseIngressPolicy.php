<?php

namespace Arbor\files\utilities;


use Arbor\files\contracts\IngressPolicyInterface;


/**
 * Abstract base class for ingress file policy implementations.
 *
 * Extends {@see BaseFilePolicy} with the {@see IngressPolicyInterface} contract,
 * scoping the policy specifically to inbound file operations such as uploads or
 * imports. Concrete subclasses implement the ingress-specific validation and
 * constraint logic (e.g. permitted MIME types, maximum file size, allowed
 * extensions) defined by {@see IngressPolicyInterface}.
 *
 * @package Arbor\files\utilities
 */
abstract class BaseIngressPolicy extends BaseFilePolicy implements IngressPolicyInterface {}
