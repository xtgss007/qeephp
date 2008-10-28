<?php

require_once dirname(__FILE__) . '/../../include/unittest_common.php';

class UnitTest_Core_Config extends QTest_UnitTest_Abstract
{
    function testGetIniWithSingleValue()
    {
        Q::setIni('string', 'text');
        $this->assertEquals('text', Q::getIni('string'), "Q::getIni('string') == 'text'");
    }

    function testGetIniWithValueSet()
    {
        $value = array('item1' => 1, 'item2' => 'i2');
        Q::setIni('the_key', $value);
        $this->assertEquals($value, Q::getIni('the_key'), "Q::getIni('the_key') == \$value");
    }

    function testGetIniWithPath()
    {
        Q::setIni('node/item', 'text');
        $this->assertEquals('text', Q::getIni('node/item'), "Q::getIni('node/item') == 'text'");
        $this->assertType('array', Q::getIni('node'), "Q::getIni('node') == array()");
    }

    function testGetIniWithInDepthPath()
    {
        Q::setIni('root/node/item', 123);
        $this->assertEquals(123, Q::getIni('root/node/item'), "Q::getIni('root/node/item') == 123");
        $this->assertType('array', Q::getIni('root/node'), "Q::getIni('root/node') == array()");
        $this->assertType('array', Q::getIni('root'), "Q::getIni('root') == array()");
    }

    function testGetIniFromValueSetByPath()
    {
        $value = array('item1' => 123, 'item2' => 'item2', 'item3' => array('si' => 456));
        Q::setIni('the_key_2', $value);
        $this->assertEquals('item2', Q::getIni('the_key_2/item2'), "Q::getIni('the_key_2/item2' == 'item2'");
        $this->assertEquals($value['item3'], Q::getIni('the_key_2/item3'), "Q::getIni('the_key_2/item3') == array");
        $this->assertEquals(456, Q::getIni('the_key_2/item3/si'), "Q::getIni('the_key_2/item3/si') == 456");
    }

    function testUnsetIni()
    {
        Q::unsetIni('root/node/item');
        $test = Q::getIni('root/node');
        $this->assertTrue(empty($test), "Q::getIni('root/node') == empty");
        Q::unsetIni('root');
        $test = Q::getIni('root');
        $this->assertTrue(empty($test), "Q::getIni('root') == empty");
    }

}

