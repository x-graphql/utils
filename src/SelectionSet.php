<?php

declare(strict_types=1);

namespace XGraphQL\Utils;

use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Introspection;

final readonly class SelectionSet
{
    /**
     * @param FragmentDefinitionNode[] $fragments
     */
    public static function addTypenameToFragments(array $fragments): void
    {
        foreach ($fragments as $fragment) {
            self::addTypename($fragment->selectionSet);
        }
    }

    public static function addTypename(SelectionSetNode $node): void
    {
        $hasTypenameNode = false;

        foreach ($node->selections as $selection) {
            if ($selection instanceof InlineFragmentNode) {
                self::addTypename($selection->selectionSet);
            }

            if ($selection instanceof FieldNode) {
                if (null !== $selection->selectionSet) {
                    self::addTypename($selection->selectionSet);
                }

                $name = $selection->name->value;
                $alias = $selection->alias?->value;

                if (
                    $alias === Introspection::TYPE_NAME_FIELD_NAME
                    || ($name === Introspection::TYPE_NAME_FIELD_NAME && null === $alias)
                ) {
                    $hasTypenameNode = true;
                }
            }
        }

        if (false === $hasTypenameNode) {
            $node->selections[] = Parser::field(Introspection::TYPE_NAME_FIELD_NAME);
        }
    }
}
