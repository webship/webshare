<?php

declare(strict_types=1);

namespace Doctrine\Common\Annotations;

/**
 * A list of annotations that are implicitly ignored during the parsing process.
 *
 *  All names are case sensitive.
 */
final class ImplicitlyIgnoredAnnotationNames {
  private const Reserved = [
    'Annotation'               => TRUE,
    'Attribute'                => TRUE,
    'Attributes'               => TRUE,
        /* Can we enable this? 'Enum' => true, */
    'Required'                 => TRUE,
    'Target'                   => TRUE,
    'NamedArgumentConstructor' => TRUE,
  ];

  private const WidelyUsedNonStandard = [
    'fix'      => TRUE,
    'fixme'    => TRUE,
    'override' => TRUE,
  ];

  private const PhpDocumentor1 = [
    'abstract'   => TRUE,
    'access'     => TRUE,
    'code'       => TRUE,
    'deprec'     => TRUE,
    'endcode'    => TRUE,
    'exception'  => TRUE,
    'final'      => TRUE,
    'ingroup'    => TRUE,
    'inheritdoc' => TRUE,
    'inheritDoc' => TRUE,
    'magic'      => TRUE,
    'name'       => TRUE,
    'private'    => TRUE,
    'static'     => TRUE,
    'staticvar'  => TRUE,
    'staticVar'  => TRUE,
    'toc'        => TRUE,
    'tutorial'   => TRUE,
    'throw'      => TRUE,
  ];

  private const PhpDocumentor2 = [
    'api'            => TRUE,
    'author'         => TRUE,
    'category'       => TRUE,
    'copyright'      => TRUE,
    'deprecated'     => TRUE,
    'example'        => TRUE,
    'filesource'     => TRUE,
    'global'         => TRUE,
    'ignore'         => TRUE,
        /* Can we enable this? 'index' => true, */
    'internal'       => TRUE,
    'license'        => TRUE,
    'link'           => TRUE,
    'method'         => TRUE,
    'package'        => TRUE,
    'param'          => TRUE,
    'property'       => TRUE,
    'property-read'  => TRUE,
    'property-write' => TRUE,
    'return'         => TRUE,
    'see'            => TRUE,
    'since'          => TRUE,
    'source'         => TRUE,
    'subpackage'     => TRUE,
    'throws'         => TRUE,
    'todo'           => TRUE,
    'TODO'           => TRUE,
    'usedby'         => TRUE,
    'uses'           => TRUE,
    'var'            => TRUE,
    'version'        => TRUE,
  ];

  private const PHPUnit = [
    'author'                         => TRUE,
    'after'                          => TRUE,
    'afterClass'                     => TRUE,
    'backupGlobals'                  => TRUE,
    'backupStaticAttributes'         => TRUE,
    'before'                         => TRUE,
    'beforeClass'                    => TRUE,
    'codeCoverageIgnore'             => TRUE,
    'codeCoverageIgnoreStart'        => TRUE,
    'codeCoverageIgnoreEnd'          => TRUE,
    'covers'                         => TRUE,
    'coversDefaultClass'             => TRUE,
    'coversNothing'                  => TRUE,
    'dataProvider'                   => TRUE,
    'depends'                        => TRUE,
    'doesNotPerformAssertions'       => TRUE,
    'expectedException'              => TRUE,
    'expectedExceptionCode'          => TRUE,
    'expectedExceptionMessage'       => TRUE,
    'expectedExceptionMessageRegExp' => TRUE,
    'group'                          => TRUE,
    'large'                          => TRUE,
    'medium'                         => TRUE,
    'preserveGlobalState'            => TRUE,
    'requires'                       => TRUE,
    'runTestsInSeparateProcesses'    => TRUE,
    'runInSeparateProcess'           => TRUE,
    'small'                          => TRUE,
    'test'                           => TRUE,
    'testdox'                        => TRUE,
    'testWith'                       => TRUE,
    'ticket'                         => TRUE,
    'uses'                           => TRUE,
  ];

  private const PhpCheckStyle = ['SuppressWarnings' => TRUE];

  private const PhpStorm = ['noinspection' => TRUE];

  private const PEAR = ['package_version' => TRUE];

  private const PlainUML = [
    'startuml' => TRUE,
    'enduml'   => TRUE,
  ];

  private const Symfony = ['experimental' => TRUE];

  private const PhpCodeSniffer = [
    'codingStandardsIgnoreStart' => TRUE,
    'codingStandardsIgnoreEnd'   => TRUE,
  ];

  private const SlevomatCodingStandard = ['phpcsSuppress' => TRUE];

  private const Phan = ['suppress' => TRUE];

  private const Rector = ['noRector' => TRUE];

  private const StaticAnalysis = [
        // PHPStan, Psalm
    'extends' => TRUE,
    'implements' => TRUE,
    'readonly' => TRUE,
    'template' => TRUE,
    'use' => TRUE,

        // Psalm
    'pure' => TRUE,
    'immutable' => TRUE,
  ];

  public const LIST = self::Reserved
        + self::WidelyUsedNonStandard
        + self::PhpDocumentor1
        + self::PhpDocumentor2
        + self::PHPUnit
        + self::PhpCheckStyle
        + self::PhpStorm
        + self::PEAR
        + self::PlainUML
        + self::Symfony
        + self::SlevomatCodingStandard
        + self::PhpCodeSniffer
        + self::Phan
        + self::Rector
        + self::StaticAnalysis;

  private function __construct() {
  }

}
