<?php

namespace Arbor\files;

use Arbor\files\Evaluator;

// Files
//  └── Variator->generate()
//         └── PolicyCatalog resolves VariantPolicyInterface
//                 └── variantPolicy->variants() → VariantProfile[]
//                         └── profile:
//                                - name()
//                                - filters()
//                                - transformers()
//                                - path()
//         └── Evaluator runs filters + transformers
//         └── variator -> persist ask storage to writes result

class Variator {}
