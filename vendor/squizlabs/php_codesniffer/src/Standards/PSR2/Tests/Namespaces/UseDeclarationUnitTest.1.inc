<?php

namespace MyProject;

/**
 * Comment here.
 */
class Foo {
}

$var = new MyClass(
    function () use ($foo, $bar) {
        return TRUE;
    }
);

class Container extends Component implements IContainer {
  use TContainer;

}

trait HelloWorld {
  use Hello, World;

}

enum SomeEnum {
  use Hello, World;
}

$x = $foo ? function ($foo) use /* comment */ ($bar): int {
  return 1;
} : $bar;
