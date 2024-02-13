<?php

declare(strict_types=1);

namespace XGraphQL\Utils;

use GraphQL\Language\AST\ArgumentNode;
use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\ListValueNode;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\ObjectFieldNode;
use GraphQL\Language\AST\ObjectValueNode;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Language\AST\ValueNode;
use GraphQL\Language\AST\VariableNode;


final readonly class Variable
{
    /**
     * @param FragmentDefinitionNode[] $fragments
     * @return string[]
     */
    public static function getVariablesInFragments(array $fragments): array
    {
        $variables = [];

        foreach ($fragments as $fragment) {
            $variables = array_merge(
                $variables,
                self::getVariablesInDirectives($fragment->directives),
                self::getVariablesInSelectionSet($fragment->selectionSet),
            );
        }

        return array_unique($variables);
    }

    /**
     * @return string[]
     */
    public static function getVariablesInOperation(OperationDefinitionNode $operation): array
    {
        $variables = array_merge(
            self::getVariablesInDirectives($operation->directives),
            self::getVariablesInSelectionSet($operation->getSelectionSet()),
        );

        return array_unique($variables);
    }

    /**
     * @return string[]
     */
    public static function getVariablesInSelectionSet(SelectionSetNode $selectionSet): array
    {
        $variables = [];

        foreach ($selectionSet->selections as $selection) {
            /** @var FragmentSpreadNode|InlineFragmentNode|FieldNode $selection */

            $variables = array_merge($variables, self::getVariablesInDirectives($selection->directives));

            if ($selection instanceof InlineFragmentNode) {
                $variables = array_merge($variables, self::getVariablesInSelectionSet($selection->selectionSet));

                continue;
            }

            if ($selection instanceof FieldNode) {
                $variables = array_merge($variables, self::getVariablesInArgs($selection->arguments));

                if (null !== $selection->selectionSet) {
                    $variables = array_merge($variables, self::getVariablesInSelectionSet($selection->selectionSet));
                }
            }
        }

        return array_unique($variables);
    }

    /**
     * @param NodeList<DirectiveNode> $directives
     * @return string[]
     */
    public static function getVariablesInDirectives(NodeList $directives): array
    {
        $variables = [];

        foreach ($directives as $directive) {
            $variables = array_merge($variables, self::getVariablesInArgs($directive->arguments));
        }

        return array_unique($variables);
    }

    /**
     * @param NodeList<ArgumentNode> $args
     * @return string[]
     */
    private static function getVariablesInArgs(NodeList $args): array
    {
        $variables = [];

        foreach ($args as $argument) {
            $variables = array_merge($variables, self::getVariablesInValue($argument->value));
        }

        return array_unique($variables);
    }

    /**
     * @return string[]
     */
    private static function getVariablesInValue(ValueNode $arg): array
    {
        $variables = [];

        if ($arg instanceof ListValueNode) {
            foreach ($arg->values as $value) {
                $variables = array_merge($variables, self::getVariablesInValue($value));
            }
        }

        if ($arg instanceof ObjectValueNode) {
            foreach ($arg->fields as $field) {
                /** @var ObjectFieldNode $field */
                $variables = array_merge($variables, self::getVariablesInValue($field->value));
            }
        }

        if ($arg instanceof VariableNode) {
            $variables[] = $arg->name->value;
        }

        return array_unique($variables);
    }
}
