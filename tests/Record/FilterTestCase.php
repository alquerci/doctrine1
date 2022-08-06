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

    public function testCompoundGet_willThrowUndefinedProperty_withGivenNameIsNotAProperty()
    {
        $this->expectException('Doctrine_Record_UnknownPropertyException');

        $composite = new CompositeRecord();

        $composite->foo;
    }

    public function testCompoundSet_willThrowUndefinedProperty_withGivenNameIsNotAProperty()
    {
        $this->expectException('Doctrine_Record_UnknownPropertyException');

        $composite = new CompositeRecord();

        $composite->foo = 'foo';
    }

    public function testCompoundGet_willThrowUndefinedProperty_withGivenNameIsNotAPropertyOnRelated()
    {
        $this->expectException('Doctrine_Record_UnknownPropertyException');

        $composite = new CompositeRecord();

        $composite->Related->foo;
    }

    public function testCompoundSet_willThrowUndefinedProperty_withGivenNameIsNotAPropertyOnRelated()
    {
        $this->expectException('Doctrine_Record_UnknownPropertyException');

        $composite = new CompositeRecord();

        $composite->Related->foo = 'foo';
    }

    public function testCompoundGet_beforeSave()
    {
        $composite = new CompositeRecord();

        $composite->address = 'foo';

        $this->assertEqual('foo', $composite->address);

        $composite->save();
    }

    public function testCompoundGet_afterSaveAddressOnRelation_willGetAdressOnRecord()
    {
        $composite = new CompositeRecord();

        $composite->Related->address = 'foo';

        $composite->save();

        $this->assertEqual('foo', $composite->address);
    }

    public function testCompoundGet_beforeSaveEmailOnRelation_willGetEmailOnRecord()
    {
        $composite = new DistinctTableCompositeRecord();

        $composite->Email->email = 'bar';

        $this->assertEqual('bar', $composite->email);

        $composite->save();
    }

    public function testCompoundSet_afterSaveAddressOnRelation_willSetAdressOnRecord()
    {
        $composite = new CompositeRecord();

        $composite->Related->address = 'foo';

        $composite->save();

        $composite->address = 'bar';

        $this->assertEqual('bar', $composite->Related->address);
    }

    public function testCompoundGet_afterSaveEmailOnRelation_willGetEmailOnRecord()
    {
        $composite = new DistinctTableCompositeRecord();

        $composite->Email->email = 'bar';

        $composite->save();

        $this->assertEqual('bar', $composite->email);
    }

    public function testCompoundGet_willUseNullValueInsteadOfFallback()
    {
        $composite = new SameTableCompositeRecord();

        $composite->Related->address = null;
        $composite->RelatedFallback->address = 'foo';

        $composite->save();

        $this->assertNull($composite->address);
    }

    public function testCompoundSet_beforeSave_willSetOnFirstRelation_withUndefinedRelationProperty()
    {
        $composite = new SameTableCompositeRecord();

        $composite->address = 'foo';

        $composite->save();

        $this->assertEqual('foo', $composite->Related->address);
        $this->assertNull($composite->RelatedFallback->address);
    }


    public function testCompoundSet_onSameTable_beforeSave_willSetOnFirstRelation_withFirstRelationIsNull()
    {
        $composite = new SameTableCompositeRecord();

        $composite->Related->address = null;

        $composite->address = 'foo';

        $composite->save();

        $this->assertEqual('foo', $composite->Related->address);
        $this->assertNull($composite->RelatedFallback->address);
    }

    public function testCompound_onSameTable_afterSave_willSetFirst_withFirstRelationIsNull()
    {
        $composite = new SameTableCompositeRecord();

        $composite->Related->address = null;

        $composite->save();

        $composite->address = 'foo';

        $this->assertEqual('foo', $composite->Related->address);
        $this->assertNull($composite->RelatedFallback->address);
    }

    public function testCompound_onDistinctTable_beforeSave_willSetOnSecondRelation_withFirstRelationIsNull()
    {
        $composite = new DistinctTableCompositeRecord();

        $composite->Related->address = null;

        $composite->email = 'foo';

        $composite->save();

        $this->assertNull($composite->Related->address);
        $this->assertEqual('foo', $composite->Email->email);
    }

    public function testCompound_onDistinctTable_afterSave_willNotSet_withFirstRelationIsNull()
    {
        $composite = new DistinctTableCompositeRecord();

        $composite->Related->address = null;

        $composite->save();

        $composite->email = 'foo';

        $this->assertNull($composite->Related->address);
        $this->assertNull($composite->Email->email); // bug? cannot set prop of fallback relation when exists
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
