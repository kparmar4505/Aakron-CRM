define(function (require) {
    'use strict';

    var Select2View,
        BaseView = require('oroui/js/app/views/base/view');
    require('jquery.select2');

    Select2View = BaseView.extend({
        autoRender: true,

        /**
         * Renders a select2 view
         */
        render: function () {
            this.$el.select2(this.options);
            return Select2View.__super__.render.call(this);
        },

        /**
         * Disposes the view
         */
        dispose: function () {
            if (this.disposed) {
                // the view is already removed
                return;
            }
            this.$el.select2('destroy');
            Select2View.__super__.dispose.call(this);
        }
    });

    return Select2View;
});