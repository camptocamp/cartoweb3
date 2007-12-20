AjaxPlugins.BboxHistory = {
    handleResponse: function(pluginOutput) {
        if ($('bbox_history_form')) {
            $('bbox_history_form').replace(pluginOutput.htmlCode.bboxHistoryForm);
        }
    }
};


AjaxPlugins.BboxHistory.Actions = {};

AjaxPlugins.BboxHistory.Actions.moveTo = {
    buildPostRequest: function(argObject) {
        return 'steps=' + $(argObject).steps;
    }
};
