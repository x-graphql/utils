<?php

declare(strict_types=1);

namespace XGraphQL\Utils\Test;

use GraphQL\Utils\BuildSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use XGraphQL\Utils\SchemaPrinter;

class SchemaPrinterTest extends TestCase
{
    #[DataProvider(methodName: 'printSchemaExcludeTypeSystemDirectivesTestCaseDataProvider')]
    public function testPrintSchemaExcludeSystemDirectives(string $sdl, string $expectSDL): void
    {
        $schema = BuildSchema::build($sdl);

        $this->assertEquals($expectSDL, SchemaPrinter::printSchemaExcludeTypeSystemDirectives($schema));
    }

    public static function printSchemaExcludeTypeSystemDirectivesTestCaseDataProvider(): array
    {
        return [
            'can exclude type system directives, single position' => [
                <<<'SDL'
directive @system_directive on OBJECT

type Query @system_directive {
  test: String!
}
SDL,
                <<<'SDL'
type Query {
  test: String!
}

SDL,
            ],
            'can exclude type system directives, multiple position' => [
                <<<'SDL'
directive @system_directive on OBJECT | FIELD_DEFINITION | INTERFACE

type Query @system_directive {
  test: String!
}

interface X @system_directive {
  id: ID! @system_directive
}
SDL,
                <<<'SDL'
type Query {
  test: String!
}

interface X {
  id: ID!
}

SDL,
            ],
            'should not remove directive if execution location exists' => [
                <<<'SDL'
directive @system_directive on OBJECT | FIELD

type Query @system_directive {
  test: String!
}
SDL,
                <<<'SDL'
directive @system_directive on OBJECT | FIELD

type Query {
  test: String!
}

SDL,
            ]
        ];
    }
}
