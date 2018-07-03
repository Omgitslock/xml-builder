<?php

namespace pdeans\Builders;

use UnexpectedValueException;
use XMLWriter;

/**
 * XmlBuilder
 *
 * Easy XML Builder
 */
class XmlBuilder extends XMLWriter
{

    protected $withoutSingleTags = false;

    /**
     * Устанавливаем без одиночных тегов
     *
     * @return $this
     */
    public function withoutSingleTags()
    {
        $this->withoutSingleTags = true;

        return $this;
    }

    /**
     * Create an xml tag
     *
     * @param string $tag_name  XML tag name
     * @param array $tags Associative array of xml tag data
     * @return string
     */
	public function create($tag_name, array $tags)
    {
		$this->openMemory();
		$this->setIndent(true);
		$this->setIndentString('    ');

		$this->startElement($tag_name);
        $this->addTags($tags);
		$this->endElement();

		return $this->outputMemory();
	}

	/**
	 * Generate child tag xml markup
	 *
	 * @param array  $tags  Child tag data
	 * @throws \UnexpectedValueException  Invalid array for reserved tag value
	 */
	protected function addTags(array $tags)
	{
		foreach ($tags as $name => $value) {
			if (is_array($value)) {
				// Check if this is a sequential array
				if ($value === array_values($value)) {
					foreach ($value as $tags) {
                        $this->addNodesTo($name, $tags);
					}
				}
				else if ($name === '@a') {
					$this->addAttributes($value);
				}
				else if ($name === '@v') {
					$this->writeRaw($value);
				}
				else if ($name === '@t') {
                    $this->addTags($value);
                }
				else {
				    $this->addNodesTo($name, $value);
				}
			}
			else if ($name === '@v') {
				$this->writeRaw($value);
			}
			else {
				$this->addTag($name, $value);
			}
		}
	}

    protected function addAttributes(array $attributes)
    {
        foreach ($attributes as $name => $value) {
            $this->writeAttribute($name, $value);
        }

        return $this;
	}


    protected function hasValue(array $node)
    {
        if(array_key_exists('@v',  $node)){
            return true;
        }

        return false;
	}

    protected function hasTag(array $node)
    {
        if(array_key_exists('@t',  $node)){
            return true;
        }

        return false;
    }

    protected function hasNullTag(array $node)
    {
        if($this->hasTag($node)){
            return empty($node['@t']);
        }

        return false;
    }

    protected function hasNullValue(array $node)
    {
        if($this->hasValue($node)){
            return $node['@v'] === null;
        }

        return false;
	}

    protected function isSkipped($nodes)
    {
        if($this->withoutSingleTags){
            if($this->hasNullValue($nodes)){
                return true;
            }

            if($this->hasNullTag($nodes)){
                return true;
            }

            if(empty($nodes)){
                return true;
            }
        }

        return false;
	}

    protected function addNodesTo($name, $nodes)
    {
        if($this->isSkipped($nodes)){
            return $this;
        }

        $this->startElement($name);
        $this->addTags($nodes);
        $this->endElement();

        return $this;
    }
	
	/**
	 * Generate a standard xml tag
	 *
	 * @param string  $tag_name  Tag name
	 * @param mixed  $value  Tag value
	 */
	protected function addTag($tag_name, $value = null)
	{
	    if($value === null && $this->withoutSingleTags){
	        return $this;
        }

        $this->startElement($tag_name);

		if ($value !== null) {
			$this->writeRaw($value);
		}

		$this->endElement();
	}

	/**
	 * Wrap value in cdata tag
	 *
	 * @param mixed  $value  Tag value
	 * @return string
	 */
	public function cdata($value)
	{
		return '<![CDATA['.$value.']]>';
	}

	/**
	 * Format decimal number
	 *
	 * @param string|int|float  $value  The decimal value
	 * @param int  $precision  Decimal precision
	 * @return string  Formatted decimal number
	 */
	public function decimal($value, $precision = 2)
	{
		return number_format((float)$value, $precision, '.', '');
	}
}