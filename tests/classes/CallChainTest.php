<?php
/*
CallChain
[0] Call
    JoinedCalls
    [1] Call
        [2] Call (Jump)
    [3] Call
        JoinedCalls
        [4] Call

CallChain
    _calls
    [0] Call
        _callChains
            X [0] CallChain
                _calls
                    [1] Call
                        _callChain
                            X [0] CallChain
                                _calls
                                    [2] Call (Jump)
                    [3] Call
                        _callChain
                            X [0] CallChain
                                _calls
                                    [4] Call
*/

class CallChainTest extends PHPUnit_Framework_TestCase {
    public function testCanBuildBasicChain() {
        $chain = new CallChain();
        $chain->push("Node1");
        $chain->push("Node2");
        $chain->push("Node3");
        $chain->push("Node4");
        $chain->push("Node5");
        $this->assertObjectHasAttribute("_calls", $chain->_calls[0]);
        //$this->assertObjectNotHasAttribute("_calls", $chain->_calls[0]);

        $this->assertEquals(1, count($chain->_calls));
        $this->assertEquals(1, count($chain->_calls[0]->_calls));
    }

    public function testCanBuildBranchedChain() {
        $chain = new CallChain();
        $chain->push("Node1", array("set:HERE"));
        $chain->push("Node2");
        $call = new Call();
        $call->jump = "HERE";
        $chain->push($call);
        $chain->push("Node3");
        $chain->push("Node4");
        $chain->push("Node5");

        $this->assertObjectHasAttribute("_calls", $chain->_calls[0]);
        //$this->assertObjectNotHasAttribute("_calls", $chain->_calls[0]);
        $this->assertEquals(1, count($chain->_calls));
        $this->assertEquals(2, count($chain->_calls[0]->_calls));
        $this->assertEquals("Node1", $chain->_calls[0]->table);
        $this->assertEquals("Node2", $chain->_calls[0]->_calls[0]->table);
    }

    public function testCanRefactorOwner1() {
       $chain = new CallChain();
        $chain->push("Node1", array("set:HERE"));
        $chain->push("Node2");
        $chain->push("Node3");
        $call = new Call();
        $call->jump = "HERE";
        $chain->push($call);
        $chain->push("Node4");
        $chain->push("Node5");

        $this->assertObjectHasAttribute("_parent", $chain->_calls[0]);
        $this->assertNotEmpty($chain->_calls[0]->_calls[0]->_parent);
        
        $chain->orderByOwnership();

        $this->assertEquals(1, count($chain->_calls));
        $this->assertEquals("Node5", $chain->_calls[0]->table);
        $this->assertObjectHasAttribute("_calls", $chain->_calls[0]);
        $this->assertEquals(1, count($chain->_calls));
        $this->assertEquals("Node5", $chain->_calls[0]->table);
        $this->assertEquals("Node4", $chain->_calls[0]->_calls[0]->table);
        $this->assertEquals("Node1", $chain->_calls[0]->_calls[0]->_calls[0]->table);
        $this->assertEquals(1, count($chain->_calls[0]->_calls[0]->_calls[0]->_calls));
        $this->assertEquals("Node2", $chain->_calls[0]->_calls[0]->_calls[0]->_calls[0]->table);
    }

    /*
     * Confirm refactoring doesn't choke on extra jumps or calls with same table name
     */
    public function testCanRefactorOwner2() {
        $chain = new CallChain();
        $chain->push("Node1", array("set:HERE"));
        $chain->push("Node2");
        $chain->push("Node3");
        $call = new Call();
        $call->jump = "HERE";
        $chain->push($call);
        $chain->push("Node4", array("set:HERE2"));
        $chain->push("Node5");
        $call = new Call();
        $call->jump = "HERE2";
        $chain->push($call);
        $chain->push("Node5");

        $this->assertObjectHasAttribute("_parent", $chain->_calls[0]);
        $this->assertNotEmpty($chain->_calls[0]->_calls[0]->_parent);

        $chain->orderByOwnership();

        $this->assertEquals(1, count($chain->_calls));
        $this->assertEquals("Node5", $chain->_calls[0]->table);
        $this->assertObjectHasAttribute("_calls", $chain->_calls[0]);
        $this->assertEquals(1, count($chain->_calls));
        $this->assertEquals("Node5", $chain->_calls[0]->table);
        $this->assertEquals("Node4", $chain->_calls[0]->_calls[0]->table);
        $this->assertEquals("Node5", $chain->_calls[0]->_calls[0]->_calls[0]->table);
        $this->assertEquals(0, count($chain->_calls[0]->_calls[0]->_calls[0]->_calls));
        $this->assertEquals("Node1", $chain->_calls[0]->_calls[0]->_calls[1]->table);
        $this->assertEquals(1, count($chain->_calls[0]->_calls[0]->_calls[1]->_calls));
        $this->assertEquals("Node2", $chain->_calls[0]->_calls[0]->_calls[1]->_calls[0]->table);
    }

    function testCanBuildWhere() {
        $chain = new CallChain();
        $chain->push("Node", array("eq:foo:1"));

    }

    /*
    function testCanBuildColumnList() {
       $callChain = new CallChain();
        $callChain->push("property", array("select:foo"));
        $callChain->push("tags");
        $callChain->orderByOwnership();
        $tables = $callChain->getColumns();

        $this->assertEquals(2, count($tables));
        $this->assertEquals("tags", $tables[0]['name']);
        $this->assertFalse($tables[0]["duplicated"]);

        $this->assertEquals("property", $tables[1]['name']);
        $this->assertEquals("/", $tables[0]["path"]);
        $this->assertFalse($tables[1]["duplicated"]);
        $this->assertEquals("/0/", $tables[1]["path"]);
        
        $this->assertEquals("*", $tables[0]['columns'][0]['name']);
    }
     * 
     */

    /*
    function testCanBuildColumnWithAlias() {
        $callChain = new CallChain();
        $callChain->push("property", array("select:foo>>bar"));
        $callChain->push("tags", array("select:foo"));
        $callChain->orderByOwnership();

        $tables = $callChain->getColumns();

        $this->assertEquals(2, count($tables));
        $this->assertEquals("foo", $tables[1]['columns'][0]['name']);
        $this->assertEquals("bar", $tables[1]['columns'][0]['alias']);
        $this->assertEmpty($tables[0]['columns'][1]['alias']);
    }
     * 
     */

    function testCanCondenseCallChain() {
        $callChain = new CallChain();
        $callChain->push("property", array("select:foo>>bar"));
        $callChain->push("tags", array("select:foo"));
        $callChain->orderByOwnership();
        DebugUtils::getChain($callChain);
        $callChain->_condensed();
    }
}
?>
