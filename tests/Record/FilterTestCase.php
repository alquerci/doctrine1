<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

/**
 * Doctrine_Record_Filter_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.doctrine-project.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Record_Filter_TestCase extends Doctrine_UnitTestCase
{
    public function tearDown()
    {
        InitTestCompositeRecord::$testHasRelatedRelation = true;

        parent::tearDown();
    }

    public function prepareData()
    {
    }

    public function prepareTables()
    {
        $this->tables = array(
            'CompositeRecord',
            'RelatedCompositeRecord',
            'DistinctTableCompositeRecord',
            'EmailRelatedCompositeRecord',
            'SameTableCompositeRecord',
            'WithRelationCompositeRecord',
            'RelationRelatedCompositeRecord',
            'WithoutAliasesCompositeRecord',
        );

        parent::prepareTables();
    }

    public function testStandardFiltersThrowsExceptionWhenGettingUnknownProperties()
    {
        $this->expectException('Doctrine_Record_Exception');

        $u = new User();

        $u->unknown;
    }

    public function testStandardFiltersThrowsExceptionWhenSettingUnknownProperties()
    {
        $this->expectException('Doctrine_Record_Exception');

        $u = new User();

        $u->unknown = 'something';
    }

    public function testCompound_willThrowTable_withAliasedRelationIsNotDefined()
    {
        $this->expectException('Doctrine_Table_Exception');

        InitTestCompositeRecord::$testHasRelatedRelation = false;

        new InitTestCompositeRecord();
    }

    public function testCompoundGet_willThrowUndefinedProperty_withPropertyDoesNotExists()
    {
        $this->expectException('Doctrine_Record_UnknownPropertyException');

        $composite = new CompositeRecord();

        $composite->foo;
    }

    public function testCompoundSet_willThrowUndefinedProperty_withPropertyDoesNotExists()
    {
        $this->expectException('Doctrine_Record_UnknownPropertyException');

        $composite = new CompositeRecord();

        $composite->foo = 'foo';
    }

    public function testCompoundSet_willThrowUndefinedProperty_withoutAliases()
    {
        $this->expectException('Doctrine_Record_UnknownPropertyException');

        $composite = new WithoutAliasesCompositeRecord();

        $composite->foo = 'foo';
    }

    public function testCompoundGet_willThrowUndefinedProperty_withoutAliases()
    {
        $this->expectException('Doctrine_Record_UnknownPropertyException');

        $composite = new WithoutAliasesCompositeRecord();

        $composite->foo;
    }

    public function testCompoundGet_withOneRelation_willReturnRelationPropertyValue()
    {
        $composite = new CompositeRecord();
        $composite->Related->address = 'foo';

        $actual = $composite->address;

        $this->assertEqual('foo', $actual);
    }

    public function testCompoundGet_withTwoDistinctTableRelations_afterGetFromSecondRelation_willReturnRelationPropertyValue()
    {
        $composite = new DistinctTableCompositeRecord();
        $composite->Email->email = 'bar';

        $actual = $composite->email;

        $this->assertEqual('bar', $actual);
    }

    public function testCompoundGet_withTwoRelationsHavingSameProperty_andFirstIsNull_willReturnFirstPropertyValue()
    {
        $composite = new SameTableCompositeRecord();
        $composite->Related->address = null;
        $composite->RelatedFallback->address = 'foo';

        $actual = $composite->address;

        $this->assertNull($actual);
    }

    public function testCompoundGet_withNewRecord_andForRelatedComponent()
    {
        $composite = new WithRelationCompositeRecord();
        $composite->Related->Address->address = 'foo';

        $actual = $composite->Address;

        $this->assertEqual('foo', $actual->address);
    }

    public function testCompoundSet_willReturnTheGivenRecord_toRespectFluentInterface()
    {
        $composite = new CompositeRecord();

        $actual = $composite->set('address', 'foo');

        $this->assertIdentical($composite, $actual);
    }

    public function testCompoundSet_withNewRecord_andForProperty()
    {
        $composite = new CompositeRecord();

        $composite->address = 'foo';

        $this->assertEqual('foo', $composite->Related->address);
    }

    public function testCompoundSet_withNewRecord_andForRelatedComponent()
    {
        $composite = new WithRelationCompositeRecord();

        $addressRecord = new RelatedCompositeRecord();
        $addressRecord->address = 'foo';

        $composite->Address = $addressRecord;

        $this->assertEqual('foo', $composite->Related->Address->address);
    }

    public function testCompoundSet_withTwoRelationsHavingSameProperty_andFirstIsNull_willSetOnlyFirstRelation()
    {
        $composite = new SameTableCompositeRecord();
        $composite->Related->address = null;
        $composite->RelatedFallback->address = 'foo';

        $composite->address = 'bar';

        $this->assertEqual('bar', $composite->Related->address);
        $this->assertEqual('foo', $composite->RelatedFallback->address);
    }

    public function testCompoundSet_withTwoRelationsHavingDistinctProperty_willCanSetOnSecondRelation()
    {
        $composite = new DistinctTableCompositeRecord();
        $composite->Related->address = 'foo';

        $composite->email = 'bar';

        $this->assertEqual('foo', $composite->Related->address);
        $this->assertEqual('bar', $composite->Email->email);
    }
}

class CompositeRecord extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string');
    }

    public function setUp()
    {
        $this->hasOne('RelatedCompositeRecord as Related', array(
            'foreign' => 'foreign_id',
        ));

        $this->unshiftFilter(new Doctrine_Record_Filter_Compound(array(
            'Related',
        )));
    }
}

class DistinctTableCompositeRecord extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string');
    }

    public function setUp()
    {
        $this->hasOne('RelatedCompositeRecord as Related', array(
            'foreign' => 'foreign_id',
        ));
        $this->hasOne('EmailRelatedCompositeRecord as Email', array(
            'foreign' => 'foreign_id',
        ));

        $this->unshiftFilter(new Doctrine_Record_Filter_Compound(array(
            'Related',
            'Email',
        )));
    }
}

class SameTableCompositeRecord extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string');
    }

    public function setUp()
    {
        $this->hasOne('RelatedCompositeRecord as Related', array(
            'foreign' => 'foreign_id',
        ));
        $this->hasOne('RelatedCompositeRecord as RelatedFallback', array(
            'foreign' => 'foreign_second_id',
        ));

        $this->unshiftFilter(new Doctrine_Record_Filter_Compound(array(
            'Related',
            'RelatedFallback',
        )));
    }
}

class WithRelationCompositeRecord extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string');
    }

    public function setUp()
    {
        $this->hasOne('RelationRelatedCompositeRecord as Related', array(
            'foreign' => 'foreign_id',
        ));

        $this->unshiftFilter(new Doctrine_Record_Filter_Compound(array(
            'Related',
        )));
    }
}

class WithoutAliasesCompositeRecord extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string');
    }

    public function setUp()
    {
        $this->unshiftFilter(new Doctrine_Record_Filter_Compound(array(
        )));
    }
}

class RelatedCompositeRecord extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('address', 'string');
        $this->hasColumn('foreign_id', 'integer');
        $this->hasColumn('foreign_second_id', 'integer');
    }
}

class EmailRelatedCompositeRecord extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('email', 'string');
        $this->hasColumn('foreign_id', 'integer');
    }
}

class RelationRelatedCompositeRecord extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('foreign_id', 'integer');

        $this->hasOne('RelatedCompositeRecord as Address', array(
            'foreign' => 'foreign_id',
        ));
    }
}

class InitTestCompositeRecord extends Doctrine_Record
{
    public static $testHasRelatedRelation = true;

    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string');
    }

    public function setUp()
    {
        if (self::$testHasRelatedRelation) {
            $this->hasOne('RelatedCompositeRecord as Related', array(
                'foreign' => 'foreign_id',
            ));
        }

        $this->unshiftFilter(new Doctrine_Record_Filter_Compound(array(
            'Related',
        )));
    }
}
