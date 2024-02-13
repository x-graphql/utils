<?php

declare(strict_types=1);

namespace XGraphQL\Utils\Test;

use GraphQL\Language\Parser;
use PHPUnit\Framework\TestCase;
use XGraphQL\Utils\Variable;

class VariableTest extends TestCase
{
    public function testGetVariablesInFragment(): void
    {
        $sdl = <<<'GQL'
fragment test on Country {
    name @include(if: $includeVar)
    languages(code: { _eq: $languageCode }, name: { _in: [$languageName] }) {
        name
        ... on AsiaLanguage {
            asiaName @skip(if: $skipVar)
            asiaCode @include(if: $includeVar)
        }
    }
}
GQL;
        $def = Parser::fragmentDefinition($sdl);
        $variables = Variable::getVariablesInFragments([$def]);

        $this->assertEquals(
            ['includeVar', 'languageCode', 'languageName', 'skipVar'],
            array_values($variables),
        );
    }

    public function testGetVariablesInDirective(): void
    {
        $sdl = <<<'GQL'
@testDirective(arg1: $a, arg2: [$b], arg3: { arg4: $c, arg5: [$d] })
GQL;
        $def = Parser::directives($sdl);
        $variables = Variable::getVariablesInDirectives($def);

        $this->assertEquals(
            ['a', 'b', 'c', 'd'],
            array_values($variables),
        );
    }

    public function testGetVariablesInOperation(): void
    {
        $sdl = <<<'GQL'
query test @operationDirective(arg1: $a, arg2: [$b], arg3: { arg4: $c, arg5: [$d] }) {
    field1 @include(if: $e)
    field2(arg1: { arg2: [$f] }) {
        field3
    }
}
GQL;
        $def = Parser::operationDefinition($sdl);
        $variables = Variable::getVariablesInOperation($def);

        $this->assertEquals(
            ['a', 'b', 'c', 'd', 'e', 'f'],
            array_values($variables),
        );
    }

    public function testGetVariablesInSelectionSet(): void
    {
        $sdl = <<<'GQL'
{
    field1 @include(if: $a)
    field2(arg1: { arg2: [$b] }) {
        ... on test @include(if: $c) {
            field3 {
                field4 @include(if: $d)
                field5(arg1: $e) @skip(if: $a)
            }
        }
    }
}
GQL;
        $def = Parser::selectionSet($sdl);
        $variables = Variable::getVariablesInSelectionSet($def);

        $this->assertEquals(
            ['a', 'b', 'c', 'd', 'e'],
            array_values($variables),
        );
    }
}
