define(function(require) {
    'use strict';

    var CustomQuoteView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export oroorder/js/app/views/shipping-cost-view
     * @extends oroui.app.views.base.View
     * @class oroorder.app.views.CustomQuoteView
     */
    CustomQuoteView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
        	classNotesAdditionalActive: 'js-additional-notes-multiselect',
        },

        /**
         * @property {jQuery}
         */
        $form: null,

        /**
         * @property {jQuery}
         */
        $fields: null,

        /**
         * @property {Object}
         */
        fieldsByName: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
        this.$el            
        .on('change', this.options.customSellerNotes, _.bind(this.additionalNoteDropdownChange, this))
    	;
            //this.initLayout().done(_.bind(this.handleLayoutInit, this));
        },
        additionalNoteDropdownChange: function(){
        	
        	var $classNotesAdditionalActive = this.$el.find(this.options.classNotesAdditionalActive);
        	//console.log($classNotesAdditionalActive)
        	var selectedValues = "";
        	this.find(".js-additional-notes-multiselect :selected").each(function() {
        		selectedValues += $(this).val() + "\n";
        	});
        	
        	this.find(".js-additional-notes-multiselect").parent().find("textarea").text(selectedValues);

        }
        
    });

    return CustomQuoteView;
});
