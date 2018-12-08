<?php

namespace Nemo64\CriticalCss\Domain\Model;


class HtmlStatistics
{
    private $tagNames = [];

    private $ids = [];

    private $classNames = [];

    private $attributes = [];

    public function addTagName(string $tagName): void
    {
        $tagName = mb_strtolower($tagName);
        $this->tagNames[$tagName] = $tagName;
    }

    public function hasTagName(string $tagName): bool
    {
        $tagName = mb_strtolower($tagName);
        return isset($this->tagNames[$tagName]);
    }

    public function getTagNames(): array
    {
        $tagNames = $this->tagNames;
        sort($tagNames);
        return $tagNames;
    }

    private function addId(string $id): void
    {
        $this->ids[$id] = $id;
    }

    public function hasId(string $id): bool
    {
        return isset($this->ids[$id]);
    }

    public function getIds(): array
    {
        $ids = $this->ids;
        sort($ids);
        return $ids;
    }

    private function addClassName(string $className): void
    {
        $className = mb_strtolower($className);
        $this->classNames[$className] = $className;
    }

    public function hasClassName(string $className): bool
    {
        $className = mb_strtolower($className);
        return isset($this->classNames[$className]);
    }

    public function getClassNames(): array
    {
        $classNames = $this->classNames;
        sort($classNames);
        return $classNames;
    }

    public function addAttribute(string $name, string $value = ''): void
    {
        $name = mb_strtolower($name);
        if (!isset($this->attributes[$name])) {
            $this->attributes[$name] = [$value => $value];
        } else {
            $this->attributes[$name][$value] = $value;
        }

        switch ($name) {
            case "class":
                $classes = array_filter(preg_split('#\s+#', $value));
                foreach ($classes as $class) {
                    $this->addClassName($class);
                }
                break;
            case "id":
                $this->addId($value);
                break;
        }
    }

    public function hasAttribute(string $name): bool
    {
        $name = mb_strtolower($name);
        return isset($this->attributes[$name]);
    }

    public function getAttributes(): array
    {
        $attributes = $this->attributes;
        ksort($attributes);
        foreach ($attributes as &$attributeValues) {
            sort($attributeValues);
        }

        return $attributes;
    }

    public function toArray(): array
    {
        return [
            'tagNames' => $this->getTagNames(),
            'ids' => array_values($this->ids),
            'classNames' => $this->getClassNames(),
            'attributes' => $this->getAttributes(),
        ];
    }
}
