<?php
/**
 * @package Tests
 * @version $Id$
 */
 
/**
 * @package Tests
 */
class ClientFilterSimple extends ClientPlugin
                         implements FilterProvider {
                       
    public function filterPostRequest(FilterRequestModifier $request) {}
    
    public function filterGetRequest(FilterRequestModifier $request) {
        $x = $request->getValue('x');
        if (!is_null($x)) {
            $request->setValue('recenter_x', $x);
        }
        $y = $request->getValue('y');
        if (!is_null($y)) {
            $request->setValue('recenter_y', $y);
        }
    }
}

?>