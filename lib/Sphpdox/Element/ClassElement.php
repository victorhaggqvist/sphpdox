<?php

namespace Sphpdox\Element;

use TokenReflection\ReflectionClass;

use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class element
 */
class ClassElement extends Element
{

    protected $nameReferences;

    /**
     * @var ReflectionClass
     */
    protected $reflection;

    /**
     * Constructor
     *
     * @param string $classname
     * @throws InvalidArgumentException
     */
    public function __construct(ReflectionClass $reflection)
    {
        parent::__construct($reflection);

        $this->nameReferences = [];
    }

    public function getPath()
    {
        return $this->reflection->getShortName() . '.rst';
    }

    /**
     * @param string $basedir
     * @param OutputInterface $output
     */
    public function build($basedir, OutputInterface $output)
    {
        $file = $basedir . DIRECTORY_SEPARATOR . $this->getPath();
        file_put_contents($file, $this->__toString());
    }

    /**
     * @see Sphpdox\Element.Element::__toString()
     */
    public function __toString()
    {
        $name = $this->reflection->getName();

        $label = str_replace('\\', '-', $name);
        $title = str_replace('\\', '\\\\', $name);
        //$title = $name;
        $string = ".. _$label:\n\n";
        $string .= str_repeat('-', strlen($title)) . "\n";
        $string .= $title . "\n";
        $string .= str_repeat('-', strlen($title)) . "\n\n";
        $string .= $this->getNamespaceElement();
        $string .= $this->getInheritanceTree();

        if ($this->reflection->isInterface()){
        	$string .= '.. php:interface:: ' ;
        } elseif ($this->reflection->isTrait()){
        	$string .= '.. php:trait:: ' ;
        } else {
        	$string .= '.. php:class:: ' ;
        }
        $string .= $this->reflection->getShortName();

        $parser = $this->getParser();

        if ($description = $parser->getDescription()) {
            $string .= "\n\n";
            $string .= $this->indent($description, 4);
        }

        foreach ($this->getSubElements() as $element) {
            $e = $element->__toString();
            if ($e) {
                $string .= "\n\n";
                $string .= $this->indent($e, 4);
            }
        }

        $string .= "\n\n";

        // Finally, fix some whitespace errors
        $string = preg_replace('/^\s+$/m', '', $string);
        $string = preg_replace('/ +$/m', '', $string);

        return $string;
    }

    protected function getSubElements()
    {
        $elements = array_merge(
            $this->getConstants(),
            $this->getProperties(),
            $this->getMethods()
        );

        return $elements;
    }

    protected function getConstants()
    {
        return array_map(function ($v) {
            return new ConstantElement($v);
        }, $this->reflection->getConstantReflections());
    }

    protected function getProperties()
    {
        return array_map(function ($v) {
            return new PropertyElement($v);
        }, $this->reflection->getProperties());
    }

    protected function getMethods()
    {
        return array_map(function ($v) {
            return new MethodElement($v);
        }, $this->reflection->getMethods());
    }

    public function getNamespaceElement()
    {
        return '.. php:namespace: '
            . str_replace('\\', '\\\\', $this->reflection->getNamespaceName())
            . "\n\n";
    }

    public function getInheritanceTree()
    {

        $parent_entries = [];

        $parents = $this->reflection->getParentClassNameList();
        $currentNamespace = $this->reflection->getNamespaceName();

        if( !empty($parents) ) {
            foreach ($parents as $key => $parent) {
                $string = ':ref:`';
                $string .= str_replace('\\', '-', $parent) . "`";
                $parent_entries[] = $string;
            }
        } else {
            return "";
        }

        $refs = join(' => ', $parent_entries);
        $title = "**Inheritance Hierarchy:**\n";

        return "$title$refs\n\n";
    }
}