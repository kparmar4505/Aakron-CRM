define(function(require) {
    'use strict';

    var LineItemView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var BaseView = require('oroui/js/app/views/base/view');
    var UnitsUtil = require('oroproduct/js/app/units-util');
    var BaseModel = require('oroui/js/app/models/base/model');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    require('jquery.validate');

    /**
     * @export orosale/js/app/views/line-item-view
     * @extends oroui.app.views.base.View
     * @class orosale.app.views.LineItemView
     */
    LineItemView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            classNotesSellerActive: 'quote-lineitem-notes-seller-active',
            productSelect: '.quote-lineitem-product-select input[type="hidden"]',
            productSkuLabel: '.quote-lineitem-product-sku-label',
            productSkuInput: '.quote-lineitem-product-free-form input[data-name="field__product-sku"]',
            productReplacementSelect: '.quote-lineitem-product-replacement-select input[type="hidden"]',
            offersQuantitySelector: '.quote-lineitem-offers-quantity input',
            offersPriceValueSelector: '.quote-lineitem-offers-price input:first',
            typeSelect: '.quote-lineitem-product-type-select',
            unitsSelect: '.quote-lineitem-offer-unit-select',
            productFreeFormInput: '.quote-lineitem-product-freeform-input',
            productReplacementFreeFormInput: '.quote-lineitem-productreplacement-freeform-input',
            unitsRoute: 'oro_product_unit_product_units',
            compactUnits: false,
            addItemButton: '.add-list-item',
            productSelectLink: '.quote-lineitem-product-select-link',
            freeFormLink: '.quote-lineitem-free-form-link',
            allowEditFreeForm: true,
            productFormContainer: '.quote-lineitem-product-form',
            freeFormContainer: '.quote-lineitem-product-free-form',
            fieldsRowContainer: '.fields-row',
            notesContainer: '.quote-lineitem-notes',
            addNotesButton: '.quote-lineitem-notes-add-btn',
            removeNotesButton: '.quote-lineitem-notes-remove-btn',
            itemsCollectionContainer: '.quote-lineitem-collection',
            itemsContainer: '.quote-lineitem-offers-items',
            itemWidget: '.quote-lineitem-offers-item',
            syncClass: 'synchronized',
            productReplacementContainer: '.quote-lineitem-product-replacement-row',
            sellerNotesContainer: '.quote-lineitem-notes-seller',
            requestsOnlyContainer: '.sale-quoteproductrequest-only',
            errorMessage: 'Sorry, an unexpected error has occurred.',
            submitButton: 'button#save-and-transit',
            customOffersPriceValueSelector: '.quote-lineitem-offers-price .currency-price-value input',
            allUnits: {},
            units: {},
            events: {
                before: 'entry-point:quote:load:before',
                after: 'entry-point:quote:load:after',
                trigger: 'entry-point:quote:trigger',
            },
            productDetail: '.quote-lineitem-product-detail',
            customSellerNotes: '.custome-seller-notes',
            productSubPrice: '.js-sub-total-price'	
        },

        /**
         * @property {int}
         */
        typeOffer: null,

        /**
         * @property {int}
         */
        typeReplacement: null,

        /**
         * @property {Boolean}
         */
        isFreeForm: false,

        /**
         * @property {Object}
         */
        units: {},

        /**
         * @property {Array}
         */
        allUnits: {},

        /**
         * @property {Object}
         */
        $el: null,

        /**
         * @property {Object}
         */
        $productSelect: null,

        /**
         * @property {Object}
         */
        $productReplacementSelect: null,

        /**
         * @property {Object}
         */
        $typeSelect: null,

        /**
         * @property {Object}
         */
        $addItemButton: null,

        /**
         * @property {Object}
         */
        $itemsContainer: null,

        /**
         * @property {Object}
         */
        $requestsOnlyContainer: null,

        /**
         * @property {Object}
         */
        $notesContainer: null,

        /**
         * @property {Object}
         */
        $sellerNotesContainer: null,

        /**
         * @property {Object}
         */
        $productReplacementContainer: null,

        /**
         * @property {LoadingMaskView|null}
         */
        loadingMask: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            if (!this.model) {
                this.model = new BaseModel();
            }

            this.options = _.defaults(options || {}, this.options);
            this.units = _.defaults(this.units, options.units);
            this.allUnits = _.defaults(this.allUnits, options.allUnits);

            this.typeOffer = options.typeOffer;
            this.typeReplacement = options.typeReplacement;

            this.isFreeForm = options.isFreeForm || false;

            this.loadingMask = new LoadingMaskView({container: this.$el});

            this.delegate('click', '.removeLineItem', this.removeRow);
            this.delegate('click', '.removeRow', this.removeOfferRow);

            this.$productSelect = this.$el.find(this.options.productSelect);
            this.$productReplacementSelect = this.$el.find(this.options.productReplacementSelect);
            this.$typeSelect = this.$el.find(this.options.typeSelect);
            this.$addItemButton = this.$el.find(this.options.addItemButton);
            this.$itemsContainer = this.$el.find(this.options.itemsContainer);
            this.$productReplacementContainer = this.$el.find(this.options.productReplacementContainer);
            this.$notesContainer = this.$el.find(this.options.notesContainer);
            this.$sellerNotesContainer = this.$el.find(this.options.sellerNotesContainer);
            this.$requestsOnlyContainer = this.$el.find(this.options.requestsOnlyContainer);
          
           
            this.$el
                .on('change', this.options.customSellerNotes, _.bind(this.customerNoteDropdownChange, this))
                .on('change', this.options.offersQuantitySelector, _.bind(this.updateProductSubPrice, this))
                .on('change', this.options.customOffersPriceValueSelector, _.bind(this.updateProductSubPrice, this))
                .on('change', this.options.productSelect, _.bind(this.onProductChanged, this))
                .on('change', this.options.productReplacementSelect, _.bind(this.onProductChanged, this))
                .on('change', this.options.typeSelect, _.bind(this.onTypeChanged, this))
                .on('click', this.options.addNotesButton, _.bind(this.onAddNotesClick, this))
                .on('click', this.options.removeNotesButton, _.bind(this.onRemoveNotesClick, this))
                .on('click', this.options.freeFormLink, _.bind(this.onFreeFormLinkClick, this))
                .on('click', this.options.productSelectLink, _.bind(this.onProductSelectLinkClick, this))
                .on('content:changed', _.bind(this.onContentChanged, this))
                
            ;
            this.updateProductSubPrice();
            if(this.$el.find(this.options.productSkuLabel).text()){
            	 this.updateProductDetail();
            }
         

            this.listenTo(mediator, this.options.events.before, this.disableSubmit);
            this.listenTo(mediator, this.options.events.after, this.enableSubmit);

            this.$typeSelect.trigger('change');

            this.$form = this.$el.closest('form');
            this.$fields = this.$el.find(':input[name]');

            this.fieldsByName = {};
            this.$fields.each(_.bind(function(i, field) {
                if (!this.fieldsByName[this.formFieldName(field)]) {
                    this.fieldsByName[this.formFieldName(field)] = [];
                }

                this.fieldsByName[this.formFieldName(field)].push($(field));
            }, this));

            this.entryPointTriggers([
                this.fieldsByName.quantity,
                this.fieldsByName.productUnit,
                this.fieldsByName.priceValue,
                this.fieldsByName.priceType
            ]);

            this.checkAddButton();
            this.checkAddNotes();
            if (this.isFreeForm && !this.options.allowEditFreeForm) {
                this.setReadonlyState();
            }

            this.updateValidation();
        },

        disableSubmit: function() {
            $(this.options.submitButton).prop('disabled', true);
        },

        enableSubmit: function() {
            $(this.options.submitButton).prop('disabled', false);
        },

        /**
         * @param {Object} field
         * @returns {String}
         */
        formFieldName: function(field) {
            var name = '';
            var nameParts = field.name.replace(/.*\[[0-9]+\]/, '').replace(/[\[\]]/g, '_').split('_');
            var namePart;

            for (var i = 0, iMax = nameParts.length; i < iMax; i++) {
                namePart = nameParts[i];
                if (!namePart.length) {
                    continue;
                }
                if (name.length === 0) {
                    name += namePart;
                } else {
                    name += namePart[0].toUpperCase() + namePart.substr(1);
                }
            }
            return name;
        },

        checkAddButton: function() {
//            var enabled = Boolean(this.getProductId()) || (this.isFreeForm && this.options.allowEditFreeForm);
//            this.$addItemButton.toggle(enabled);
        },

        removeOfferRow: function() {
            mediator.trigger(this.options.events.trigger);
        },

        removeRow: function() {
            this.$el.trigger('content:remove');
            this.remove();

            mediator.trigger(this.options.events.trigger);
        },

        /**
         * @param {jQuery|Array} fields
         */
        entryPointTriggers: function(fields) {
            _.each(fields, function(fields) {
                _.each(fields, function(field) {
                    $(field).attr('data-entry-point-trigger', true);
                });
            });

            mediator.trigger('entry-point:quote:init');
        },

        /**
         * Handle Product change
         *
         * @param {jQuery.Event} e
         */
        onProductChanged: function(e) {
            this.checkAddButton();

            if (this.getProductId() && !this.$itemsContainer.children().length) {
                this.$addItemButton.click();
            }

            if (this.$itemsContainer.children().length) {
                this.updateContent(true);
            }

            this.updateSkuLabel();
            this.updateProductSubPrice();
            this.updateProductDetail();
            var $quantitySelector = this.$el.find(this.options.offersQuantitySelector);
            $quantitySelector.trigger('change');

            mediator.trigger(this.options.events.trigger);
        },

        /**
         * Handle Type change
         *
         * @param {jQuery.Event} e
         */
        onTypeChanged: function(e) {
            var typeValue = parseInt(this.$typeSelect.val());

            this.$productReplacementContainer.toggle(this.typeReplacement === typeValue);
            this.$requestsOnlyContainer.toggle(this.typeOffer !== typeValue);

            this.$productSelect.trigger('change');
        },

        /**
         * Handle Content change
         *
         * @param {jQuery.Event} e
         */
        onContentChanged: function(e) {
            this.updateContent(false);
        },

        /**
         * @param {Boolean} force
         */
        updateContent: function(force) {
            this.updateValidation();

            var productId = this.getProductId();
            var productUnits = productId ? this.units[productId] : this.allUnits;

            if (!productId || productUnits) {
                this.updateProductUnits(productUnits, force || false);
            } else {
                var self = this;
                var routeParams = {'id': productId};

                if (this.options.compactUnits) {
                    routeParams.short = true;
                }

                $.ajax({
                    url: routing.generate(this.options.unitsRoute, routeParams),
                    type: 'GET',
                    beforeSend: function() {
                        self.loadingMask.show();
                    },
                    success: function(response) {
                        self.units[productId] = response.units;
                        self.updateProductUnits(response.units, force || false);
                    },
                    complete: function() {
                        self.loadingMask.hide();
                    },
                    errorHandlerMessage: __(this.options.errorMessage)
                });
            }
        },

        /**
         * Update available ProductUnit select
         *
         * @param {Object} data
         * @param {Boolean} force
         */
        updateProductUnits: function(data, force) {
            var self = this;

            self.model.set('product_units', data || {});

            var widgets = self.$el.find(self.options.itemWidget);

            $.each(widgets, function(index, widget) {
                var $select = $(widget).find(self.options.unitsSelect);

                if (!force && $select.hasClass(self.options.syncClass)) {
                    return;
                }

                UnitsUtil.updateSelect(self.model, $select);
                $select.addClass(self.options.syncClass);
            });

            if (force) {
                this.$el.find('select').inputWidget('refresh');
            }
        },
        
        /**
         * Check Add Notes
         */
        checkAddNotes: function() {
            if (this.$sellerNotesContainer.find('textarea').val()) {
                this.$notesContainer.addClass(this.options.classNotesSellerActive);
                this.$sellerNotesContainer.find('textarea').focus();
            }
        },

        /**
         * Handle Add Notes click
         *
         * @param {jQuery.Event} e
         */
        onAddNotesClick: function(e) {
            e.preventDefault();

            this.$notesContainer.addClass(this.options.classNotesSellerActive);
            this.$sellerNotesContainer.find('textarea').focus();
        },

        /**
         * Handle Remove Notes click
         *
         * @param {jQuery.Event} e
         */
        onRemoveNotesClick: function(e) {
            e.preventDefault();

            this.$notesContainer.removeClass(this.options.classNotesSellerActive);
            this.$sellerNotesContainer.find('textarea').val('');
        },

        /**
         * Handle Free Form for Product click
         *
         * @param {jQuery.Event} e
         */
        onFreeFormLinkClick: function(e) {
            e.preventDefault();

            this.clearInputs();

            this.$el.find(this.options.productFormContainer).hide();
            this.$el.find(this.options.freeFormContainer).show();

            this.isFreeForm = true;

            this.checkAddButton();
        },

        /**
         * Handle Product Form click
         *
         * @param {jQuery.Event} e
         */
        onProductSelectLinkClick: function(e) {
            e.preventDefault();

            this.clearInputs();

            this.$el.find(this.options.productFormContainer).show();
            this.$el.find(this.options.freeFormContainer).hide();

            this.isFreeForm = false;

            this.checkAddButton();
        },

        clearInputs: function() {
            this.$el.find(this.options.productFormContainer)
                .find('input').val('').change()
            ;
            this.$el.find(this.options.freeFormContainer)
                .find('input').val('')
            ;

            var self = this;

            var widgets = this.$el.find(this.options.itemWidget);

            $.each(widgets, function(index, widget) {
                var $priceValue = $(widget).find(self.options.offersPriceValueSelector);

                $priceValue.addClass('matched-price');
            });

            this.updateSkuLabel();
            this.updateProductDetail();
            this.updateProductSubPrice();
        },
        customerNoteDropdownChange: function(){
        	//alert(this.find(".js-seller-notes :selected").text());
        	var $classNotesSellerActive = this.$el.find(this.options.classNotesSellerActive);
        	
        	var selectedValues = "";
        	this.find(".js-seller-notes :selected").each(function() {
        		selectedValues += $(this).val() + "\n";
        	});
        	this.$notesContainer.find('.quote-lineitem-notes-seller-active').find("textarea").text(selectedValues);

        },
        updateSkuLabel: function() {
            var productData = this.$el.find(this.options.productSelect).inputWidget('data') || {};
            this.$el.find(this.options.productSkuLabel).text(productData.sku || '');
        },
        updateProductSubPrice: function() {
        	var self = this;
        	var data = { } ;
        	var widgets = self.$el.find(self.options.itemWidget);
        	
            $.each(widgets, function(index, widget) {
            	 var $productSubPrice = $(widget).find(self.options.productSubPrice);
             	var $offersQuantitySelector = $(widget).find(self.options.offersQuantitySelector);
             	var $offersPriceValueSelector = $(widget).find(self.options.customOffersPriceValueSelector);
             	
             	 var subtotal = ($offersQuantitySelector.val() * $offersPriceValueSelector.val()).toFixed(2);
             	$(widget).find(".quote-lineitem-offers-more-fields").hide();
             	$(widget).find(".quote-lineitem-offers-item-add").hide();
             	$(widget).find(".fields-row td:nth-child(2)").hide();
             	 $productSubPrice.text(subtotal);
            });
       },
        updateProductDetail: function() {
        	var data = { } ;
        	var productDetailObj = this.$el.find(this.options.productDetail);
        	$( productDetailObj).html("");
        	var url = $( productDetailObj).data("url");
        	
            $.ajax({

                url: url+"/"+this.$el.find(this.options.productSkuLabel).text(),
                type: 'GET',
                dataType: 'json',
                data: data,               
                success: function (data, textStatus, xhr) {
                	/**** Feature Detail *******/
               	 	$.each(data.feature_details, function( index, myvalue ) {     
	               	 	  if(myvalue.label == "Pricing Includes")
	     				  {
		               	 	var $newdiv1 = $( "<p><b>"+myvalue.label + ":</b> " + myvalue.value+"</p>" );
		              		 $( productDetailObj).append($newdiv1);
	     				  }
	               		 
               		});
               	    /**** Feature Detail *******/
	               	
               	 	/**** Product Detail *******
               	 	$( productDetailObj).html(data.basic_information.description).find('br').remove();
	               	/**** Product Detail *******/
               	    /**** Product charges *******/
               	 	try
               	 	{
	               	 	$.each(data.imprint_information.imprint_method_information, function( index, myvalue ) { 
	               			$.each(myvalue.charges, function( childIndex, childValue ) {
	           			 		if(childIndex == "SETUP_CHARGE")
	           			 		{
			               			var $newdiv1 = $( "<p><b>"+childValue.label + ": </b><input type='text' value='"+childValue.charge+"' id='abc' width='150' />"+"</p>" );
			               			$( productDetailObj).append($newdiv1);
		               			}
	               			});	               		  	               		 
		           		});
               	 	}catch(e)
               	 	{
               	 		
               	 	}
                	
                	
//                	/**** Feature Detail *******/
//               	 	$.each(data.feature_details, function( index, myvalue ) {     
//	               	 	  if(myvalue.label == "Pricing Includes")
//	     				  {
//		               	 	var $newdiv1 = $( "<p><b>"+myvalue.label + ":</b> " + myvalue.value+"</p>" );
//		               	 $( productDetailObj).append($newdiv1);
//		              		//$( productDetailObj).find(".js-pricing-included-html").html($newdiv1);
//		              		//$( productDetailObj).find(".js-pricing-included").val(myvalue.value);
//		              		//$( productDetailObj).find(".js-pricing-included-html").attr("style","width:200px;word-wrap: break-word;")
//	     				  }
//	               		 
//               		});
//               	    /**** Feature Detail *******/
//	               	
//               	 	/**** Product Detail *******
//               	 	$( productDetailObj).html(data.basic_information.description).find('br').remove();
//	               	/**** Product Detail *******/
//               	    /**** Product charges *******/
//               	 	try
//               	 	{
//	               	 	$.each(data.imprint_information.imprint_method_information, function( index, myvalue ) { 
//	               			$.each(myvalue.charges, function( childIndex, childValue ) {console.log("====")
//	           			 		if(childIndex == "SETUP_CHARGE")
//	           			 		{
//	           			 			//alert($( productDetailObj).find(".js-setup-charge").attr("name"))
//			               			var $newdiv1 = $( "<p><b>"+childValue.label + ": </b><input type='text' value='"+childValue.charge+"' id='abc' width='150' />"+"</p>" );
//			               			$( productDetailObj).append($newdiv1);
////	           			 			$( productDetailObj).find(".js-setup-charge").val(childValue.charge);
////	           			 			$( productDetailObj).find(".js-setup-charge").attr("style","width:100px")
//		               			}
//	               			});	               		  	               		 
//		           		});
//               	 	}catch(e)
//               	 	{
//               	 		
//               	 	}
     	 
	               	/**** Product charges *******/
                },
                error: function (xhr, textStatus, errorThrown) {
                    console.log('Error in Operation');
                }

            });
        },
        /**
         * Get Product Id
         */
        getProductId: function() {
            return this.isProductReplacement() ? this.$productReplacementSelect.val() : this.$productSelect.val();
        },

        /**
         * Check that Product is Replacement
         */
        isProductReplacement: function() {
            return this.typeReplacement === parseInt(this.$typeSelect.val());
        },

        /**
         * Validation for products
         */
        updateValidation: function() {
            var self = this;

            self.$el.find(self.options.productFreeFormInput).rules('add', {
                required: {
                    param: true,
                    depends: function() {
                        return !self.isProductReplacement() && self.isFreeForm;
                    }
                },
                messages: {
                    required: __('oro.sale.quoteproduct.free_form_product.blank')
                }
            });

            self.$el.find(self.options.productReplacementFreeFormInput).rules('add', {
                required: {
                    param: true,
                    depends: function() {
                        return self.isProductReplacement() && self.isFreeForm;
                    }
                },
                messages: {
                    required: __('oro.sale.quoteproduct.free_form_product.blank')
                }
            });

            self.$productSelect.rules('add', {
                required: {
                    param: true,
                    depends: function() {
                        return !self.isProductReplacement() && !self.isFreeForm;
                    }
                },
                messages: {
                    required: __('oro.sale.quoteproduct.product.blank')
                }
            });

            self.$productReplacementSelect.rules('add', {
                required: {
                    param: true,
                    depends: function() {
                        return self.isProductReplacement() && !self.isFreeForm;
                    }
                },
                messages: {
                    required: __('oro.sale.quoteproduct.product.blank')
                }
            });
        },

        /**
         * Disable items update
         */
        setReadonlyState: function() {
            var self = this;

            self.$el.find(self.options.productFreeFormInput).prop('readonly', true);
            self.$el.find(self.options.productSkuInput).prop('readonly', true);
            self.$el.find(self.options.productReplacementFreeFormInput).prop('readonly', true);
            self.$el.find('.removeLineItem').prop('disabled', true);
            self.$el.find('.removeRow').prop('disabled', true);

            var widgets = this.$el.find(self.options.itemWidget);
            $.each(widgets, function(index, widget) {
                $(widget).find(self.options.unitsSelect).prop('readonly', true);
                $(widget).find(self.options.offersPriceValueSelector).prop('readonly', true);
                $(widget).find(self.options.offersQuantitySelector).prop('readonly', true);
            });

        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off();

            LineItemView.__super__.dispose.call(this);
        }
    });

    return LineItemView;
});
