AjaxPlugins.ExportPdf = {

    onAfterAjaxCallGeneral: function(argObject) {
        // redisplay the pdf caneva after recentering
        if ($('tool').value == 'pdfrotate') {
            enableTool('pdfrotate');
        }
        var recenter_bbox = $('recenter_bbox');
        if (recenter_bbox) {
          recenter_bbox.value = '';
        }
    }  
}