<?php
/*
 * This file is part of phindexer package.
 *
 * (c) 2018 Nicolas Marniesse <nicolas.marniesse@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace NMarniesse\Phindexer\Test\Unit;

use atoum\test;
use NMarniesse\Phindexer\Collection as TestedClass;
use NMarniesse\Phindexer\IndexType\ExpressionIndex;
use NMarniesse\Phindexer\Test\Unit\Fixture\FixtureProvider;

/**
 * Class Collection
 *
 * @package NMarniesse\Phindexer\Test\Unit
 * @author  Nicolas Marniesse <nicolas.marniesse@phc-holding.com>
 */
class Collection extends test
{
    /**
     * testConstruct
     */
    public function testConstruct()
    {
        $this
            ->assert('Test constructor of Collection class.')
            ->when($tested_instance = new TestedClass(FixtureProvider::getAssociativeArray()))
                ->object($tested_instance)->isInstanceOf(TestedClass::class)
            ;
    }

    /**
     * testAddColumnIndex
     */
    public function testAddColumnIndex()
    {
        $this
            ->assert('A column index can be added.')
            ->given($tested_instance = new TestedClass(FixtureProvider::getAssociativeArray()))
            ->when($res = $tested_instance->addColumnIndex('category'))
                ->object($res)->isInstanceOf(TestedClass::class)

            ->assert('Exception when trying to index an unknown column.')
            ->exception(function () use ($tested_instance) {
                $tested_instance->addColumnIndex('unknown');
            })
                ->isInstanceOf(\RuntimeException::class)->hasMessage('Undefined index: unknown');
            ;
    }

    /**
     * testFindWhere
     */
    public function testFindWhere()
    {
        $this
            ->assert('Use index to return rows kinked to a category.')
            ->given($tested_instance = new TestedClass(FixtureProvider::getAssociativeArray()))
            ->and($tested_instance->addColumnIndex('category'))
            ->when($res = $tested_instance->findWhere('category', 'enceinte'))
                ->object($res)->isInstanceOf(TestedClass::class)
                ->boolean($this->collectionIsEmpty($res))->isFalse
                ->boolean($this->checkResults($res, 'category', 'enceinte'))->isTrue

            ->when($res = $tested_instance->findWhere('category', 'unknown'))
                ->object($res)->isInstanceOf(TestedClass::class)
                ->boolean($this->collectionIsEmpty($res))->isTrue

            ->assert('Create index if not created and return rows kinked to a category.')
            ->and($tested_instance->addColumnIndex('category'))
            ->when($res = $tested_instance->findWhere('name', 'A'))
                ->object($res)->isInstanceOf(TestedClass::class)

            ->assert('Exception when find on unknown column.')
            ->exception(function () use ($tested_instance) {
                $tested_instance->findWhere('unknown', 'enceinte');
            })
                ->isInstanceOf(\RuntimeException::class)->hasMessage('Undefined index: unknown')
            ;
    }

    /**
     * testAddExpressionIndex
     */
    public function testAddExpressionIndex()
    {
        $this
            ->assert('An expression index can be added.')
            ->given($tested_instance = new TestedClass(FixtureProvider::getAssociativeArray()))
            ->and($expression = new ExpressionIndex(function ($item) {
                foreach (['price', 'category'] as $column) {
                    if (!array_key_exists($column, $item)) {
                        throw new \RuntimeException(sprintf('Undefined index: %s', $column));
                    }
                }

                return $item['price'] > 50 && $item['category'] = 'enceintes';
            }))
            ->when($res = $tested_instance->addExpressionIndex($expression))
                ->object($res)->isInstanceOf(TestedClass::class)

            ->assert('Exception when trying to index an unknown column.')
            ->exception(function () use ($tested_instance, $expression) {
                $tested_instance->addExpressionIndex(new ExpressionIndex(function ($item) {
                    foreach (['unknown', 'category'] as $column) {
                        if (!array_key_exists($column, $item)) {
                            throw new \RuntimeException(sprintf('Undefined index: %s', $column));
                        }
                    }

                    return $item['unknown'] > 50 && $item['category'] = 'enceintes';
                }));
            })
                ->isInstanceOf(\RuntimeException::class)->hasMessage('Undefined index: unknown');
            ;
    }

    /**
     * testFindWhereExpression
     */
    public function testFindWhereExpression()
    {
        $this
            ->assert('Use index to return rows kinked to a category.')
            ->given($tested_instance = new TestedClass(FixtureProvider::getAssociativeArray()))
            ->and($expression = new ExpressionIndex(function ($item) {
                foreach (['price', 'category'] as $column) {
                    if (!array_key_exists($column, $item)) {
                        throw new \RuntimeException(sprintf('Undefined index: %s', $column));
                    }
                }

                return $item['price'] > 50 && $item['category'] = 'enceinte';
            }))
            ->and($tested_instance->addExpressionIndex($expression))
            ->when($res = $tested_instance->findWhereExpression($expression, true))
                ->object($res)->isInstanceOf(TestedClass::class)
                ->boolean($this->collectionIsEmpty($res))->isFalse
                ->boolean($this->contains($res, 1))->isTrue
                ->boolean($this->contains($res, 2))->isTrue
                ->boolean($this->contains($res, 3))->isFalse
                ->boolean($this->contains($res, 4))->isFalse
                ->boolean($this->contains($res, 5))->isFalse

            ->when($res = $tested_instance->findWhereExpression($expression, false))
                ->object($res)->isInstanceOf(TestedClass::class)
                ->boolean($this->collectionIsEmpty($res))->isFalse
                ->boolean($this->contains($res, 1))->isFalse
                ->boolean($this->contains($res, 2))->isFalse
                ->boolean($this->contains($res, 3))->isTrue
                ->boolean($this->contains($res, 4))->isTrue
                ->boolean($this->contains($res, 5))->isTrue

            ->when($res = $tested_instance->findWhereExpression($expression, '12'))
                ->object($res)->isInstanceOf(TestedClass::class)
                ->boolean($this->collectionIsEmpty($res))->isTrue
            ;
    }

    /**
     * @param TestedClass $collection
     * @param string      $column
     * @param string      $value
     * @return bool
     */
    protected function checkResults(TestedClass $collection, string $column, string $value): bool
    {
        foreach ($collection as $item) {
            if ($item[$column] !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param TestedClass $collection
     * @param int         $id
     * @return bool
     */
    protected function contains(TestedClass $collection, int $id): bool
    {
        foreach ($collection as $item) {
            if ($item['id'] === $id) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param TestedClass $collection
     * @return bool
     */
    protected function collectionIsEmpty(TestedClass $collection): bool
    {
        foreach ($collection as $item) {
            return false;
        }

        return true;
    }
}