<?php

class ClientFilterIdrecenter extends ClientPlugin
                             implements FilterProvider {
                       
    function filterPostRequest(FilterRequestModifier $request) {}
    
    function filterGetRequest(FilterRequestModifier $request) {
        
        $id = $request->getValue('id');
        if (!is_null($id)) {
            $layer = 'grid_classhilight';
            $request->setValue('selection_layer', $layer);
            $request->setValue('selection_maskmode', '1');
            $request->setValue('id_recenter_layer', $layer);
        
            $request->setValue('selection_select', $id);
            $request->setValue('id_recenter_ids', $id);
        }
    }
}

?>