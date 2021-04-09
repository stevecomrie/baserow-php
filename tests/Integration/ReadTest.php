<?php

use PHPUnit\Framework\TestCase;

use \Scomrie\Baserow\Baserow;

class ReadTest extends TestCase
{
    public function testGetRowBasic()
    {
        $baserow = new Baserow(['api_key' => $_ENV['BASEROW_API']]);

        $record1 = $baserow->get( 16453, 1 );

        $this->assertEquals( $record1->id, 1 );
        $this->assertEquals( $record1->field_75605, "Test Row 1" );
        $this->assertEquals( $record1->field_75616, "Lorem ipsum dolor sit amet" );
        $this->assertEquals( $record1->field_75606, "Lorem ipsum dolor sit amet, consectetur adipiscing elit.\n\nPellentesque nec felis sit amet nibh mattis lobortis sit amet at enim." );
        $this->assertTrue( $record1->field_75607 );
        $this->assertEquals( $record1->field_75617, 1 );
        $this->assertEquals( $record1->field_75618, 1.1 );
        $this->assertEquals( $record1->field_75619, "2021-01-01" );
        $this->assertEquals( $record1->field_75620, "https://www.baserow.io" );
        $this->assertEquals( $record1->field_75621, "stevecomrie@gmail.com" );
        $this->assertEquals( $record1->field_75624, "1-800-555-5555" );
        $this->assertEquals( $record1->field_75630->id, 2307 );
        $this->assertEquals( $record1->field_75630->value, "Option 1" );
        $this->assertCount( 1, $record1->field_75637 );
        $this->assertEquals( $record1->field_75637[0]->id, 3 );
        $this->assertEquals( $record1->field_75637[0]->value, "Test Relation 1" );

        $record2 = $baserow->get( 16453, 2 );

        $this->assertEquals( $record2->id, 2 );
        $this->assertEquals( $record2->field_75605, "Test Row 2" );
        $this->assertEquals( $record2->field_75616, "" );
        $this->assertEquals( $record2->field_75606, "" );
        $this->assertFalse( $record2->field_75607 );
        $this->assertEquals( $record2->field_75617, 0 );
        $this->assertEquals( $record2->field_75618, "0.0" );
        $this->assertEquals( $record2->field_75619, "2000-01-01" );
        $this->assertEquals( $record2->field_75620, "ftp://test.com" );
        $this->assertEquals( $record2->field_75621, "steve.comrie+baserow@gmail.com" );
        $this->assertEquals( $record2->field_75624, "555-5555" );
        $this->assertEquals( $record2->field_75630->id, 2309 );
        $this->assertEquals( $record2->field_75630->value, "Option 3" );
        $this->assertCount( 2, $record2->field_75637 );
        $this->assertEquals( $record2->field_75637[0]->id, 3 );
        $this->assertEquals( $record2->field_75637[0]->value, "Test Relation 1" );
        $this->assertEquals( $record2->field_75637[1]->id, 4 );
        $this->assertEquals( $record2->field_75637[1]->value, "Test Relation 2" );
    }

    public function testGetRowMapped() {

        $baserow = new Baserow(['api_key' => $_ENV['BASEROW_API']]);
        $baserow->setTableMap([
            'ReadTest' => [ 16453, [
                'name'         => 'field_75605',
                'singleText'   => 'field_75616',
                'longText'     => 'field_75606',
                'boolean'      => 'field_75607',
                'integer'      => 'field_75617',
                'decimal'      => 'field_75618',
                'date'         => 'field_75619',
                'url'          => 'field_75620',
                'email'        => 'field_75621',
                'phone'        => 'field_75624',
                'singleSelect' => 'field_75630',
                'relation'     => 'field_75637',
            ]],
        ]);

        $mappedRecord1 = $baserow->get( 'ReadTest', 1 );

        $this->assertEquals( $mappedRecord1->id, 1 );
        $this->assertEquals( $mappedRecord1->name, "Test Row 1" );
        $this->assertEquals( $mappedRecord1->singleText, "Lorem ipsum dolor sit amet" );
        $this->assertEquals( $mappedRecord1->longText, "Lorem ipsum dolor sit amet, consectetur adipiscing elit.\n\nPellentesque nec felis sit amet nibh mattis lobortis sit amet at enim." );
        $this->assertTrue( $mappedRecord1->boolean );
        $this->assertEquals( $mappedRecord1->integer, 1 );
        $this->assertEquals( $mappedRecord1->decimal, 1.1 );
        $this->assertEquals( $mappedRecord1->date, "2021-01-01" );
        $this->assertEquals( $mappedRecord1->url, "https://www.baserow.io" );
        $this->assertEquals( $mappedRecord1->email, "stevecomrie@gmail.com" );
        $this->assertEquals( $mappedRecord1->phone, "1-800-555-5555" );
        $this->assertEquals( $mappedRecord1->singleSelect->id, 2307 );
        $this->assertEquals( $mappedRecord1->singleSelect->value, "Option 1" );
        $this->assertCount( 1, $mappedRecord1->relation );
        $this->assertEquals( $mappedRecord1->relation[0]->id, 3 );
        $this->assertEquals( $mappedRecord1->relation[0]->value, "Test Relation 1" );
    }
}
