<?php

declare(strict_types=1);

namespace Rector\Doctrine\CodeQuality\Rector\Property;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Rector\Doctrine\Tests\CodeQuality\Rector\Property\OrderByKeyToClassConstRector\OrderByKeyToClassConstRectorTest
 */
final class OrderByKeyToClassConstRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace OrderBy Attribute ASC/DESC with enum Ordering from Criteria',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
                    <?php

                    use Doctrine\Common\Collections\Criteria;

                    $criteria = new Criteria();
                    $criteria->orderBy(['someProperty' => 'ASC', 'anotherProperty' => 'DESC']);

                    ?>
                    CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
                    <?php

                    use Doctrine\Common\Collections\Criteria;

                    $criteria = new Criteria();
                    $criteria->orderBy(['someProperty' => \Doctrine\Common\Collections\Order::Ascending, 'anotherProperty' => \Doctrine\Common\Collections\Order::Descending]);

                    ?>
                    CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Node\Expr\MethodCall::class];
    }

    /**
     * @param Node\Expr\MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        // TODO this rule should not apply on PHP versions earlier that 8.1.0 and doctrine collection greater that 2.2.0
        if (! $node->name instanceof Node\Identifier) {
            return null;
        }

        if ((string) $node->name !== 'orderBy') {
            return null;
        }

        if (count($node->args) < 1) {
            return null;
        }

        // TODO find a way to ensure that the 'orderBy' method does apply on a \Doctrine\Common\Collections\Criteria instance

        $arg = $node->args[0];
        if (! $arg instanceof Node\Arg) {
            return null;
        }

        if (! $arg->value instanceof Array_) {
            return null;
        }

        $nodeHasChange = false;
        // we parse the array here
        foreach ($arg->value->items as $key => $item) {
            if ($item === null) {
                continue;
            }

            if ($item->value instanceof String_) {
                $value = $item->value->value;
                if ($value === 'ASC' || $value === 'asc') {
                    $node->args[0]->value->items[$key]->value = $this->nodeFactory->createClassConstFetch(
                        'Doctrine\Common\Collections\Order',
                        'Ascending'
                    );
                    $nodeHasChange = true;
                } elseif ($value === 'DESC' || $value === 'desc') {
                    $node->args[0]->value->items[$key]->value = $this->nodeFactory->createClassConstFetch(
                        'Doctrine\Common\Collections\Order',
                        'Descending'
                    );
                    $nodeHasChange = true;
                }
            } elseif ($item->value instanceof Node\Expr\ClassConstFetch) {
                if (! in_array('Criteria', $item->value->class->getParts())) {
                    // TODO find a way to identify
                    continue;
                }

                if ($item->value->name->toString() === 'ASC') {
                    $node->args[0]->value->items[$key]->value = $this->nodeFactory->createClassConstFetch(
                        'Doctrine\Common\Collections\Order',
                        'Ascending'
                    );
                    $nodeHasChange = true;
                } elseif ($item->value->name->toString() === 'DESC') {
                    $node->args[0]->value->items[$key]->value = $this->nodeFactory->createClassConstFetch(
                        'Doctrine\Common\Collections\Order',
                        'Descending'
                    );
                    $nodeHasChange = true;
                }
            }

        }

        if ($nodeHasChange) {
            return $node;
        }

        return null;
    }
}
