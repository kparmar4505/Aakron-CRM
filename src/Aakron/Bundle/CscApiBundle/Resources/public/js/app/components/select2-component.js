define(function (require) {
    'use strict';

    var Select2Component,
        BaseComponent = require('oroui/js/app/components/base/component');
    require('jquery.select2');

    Select2Component = BaseComponent.extend({
        /**
         * Initializes Select2 component
         *
         * @param {Object} options
         */
        initialize: function (options) {
            // _sourceElement refers to the HTMLElement
            // that contains the component declaration
            this.$elem = options._sourceElement;
            console.log(this.$elem);
            delete options._sourceElement;
            this.$elem.select2(options);
            Select2Component.__super__.initialize.call(this, options);
        },

        /**
         * Disposes the component
         */
        dispose: function () {
            if (this.disposed) {
                // component is already removed
                return;
            }
            this.$elem.select2('destroy');
            delete this.$elem;
            Select2Component.__super__.dispose.call(this);
        }
    });

    return Select2Component;
});