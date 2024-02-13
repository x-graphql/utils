<?php

declare(strict_types=1);

namespace XGraphQL\Utils\Test;

use GraphQL\Language\Parser;
use GraphQL\Language\Printer;
use PHPUnit\Framework\TestCase;
use XGraphQL\Utils\SelectionSet;

class SelectionSetTest extends TestCase
{
    public function testAddTypenameToSelectionSet(): void
    {
        $sdl = <<<'GQL'
{
  countries {
    name
    languages {
      name
    }
  }
  animals {
    __typename
    name
    ... on Human {
      national
      country {
        name
        languages {
          name
        }
      }
    }
  }
}
GQL;
        $sdlExpecting = <<<'GQL'
{
  countries {
    name
    languages {
      name
      __typename
    }
    __typename
  }
  animals {
    __typename
    name
    ... on Human {
      national
      country {
        name
        languages {
          name
          __typename
        }
        __typename
      }
      __typename
    }
  }
  __typename
}
GQL;
        $def = Parser::selectionSet($sdl);
        SelectionSet::addTypename($def);

        $actual = Printer::doPrint($def);

        $this->assertEquals($sdlExpecting, $actual);
    }

    public function testAddTypenameToFragment(): void
    {
        $sdl = <<<'GQL'
fragment test on Animal {
  name
  ... on Human {
    national
    country {
      name
      languages {
        name
      }
    }
  }
}
GQL;
        $sdlExpecting = <<<'GQL'
fragment test on Animal {
  name
  ... on Human {
    national
    country {
      name
      languages {
        name
        __typename
      }
      __typename
    }
    __typename
  }
  __typename
}
GQL;

        $def = Parser::fragmentDefinition($sdl);
        SelectionSet::addTypenameToFragments([$def]);

        $actual = Printer::doPrint($def);

        $this->assertEquals($sdlExpecting, $actual);
    }
}
