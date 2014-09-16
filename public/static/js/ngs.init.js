$(function() {
    // init forms ngs_collection
    FormEntity.loader();
    
    // init ngs lookup
    // NGS.grid.loader();

    $(document).on('focus', '.has-datepicker', null, function() {
        $(this).datetimepicker({
            minView: 2
        }).on('changeDate', function(ev) {
            $(this).datetimepicker('hide');
        }).datetimepicker('show');
    });

    $(document).on('focus', '.has-datetimepicker', null, function() {
        $(this).datetimepicker().datetimepicker('show');
    });
    
    var GridSelector = function(grid)
    {
        var self = this;
        self.grid = grid;
        this.init();
        
        self.grid.findSelected = function() { return self.findSelected() };
    };
    
    GridSelector.prototype.init = function() {
        var self = this;
        self.findRows().click(function() {
            self.grid.trigger($(this).is(':checked') ? 'grid.row.select' : 'grid.row.deselect',
                [ $(this).closest('tr').data('uri') ] );
        });
    }
    
    GridSelector.prototype.findRows = function() {
        return this.grid.find('.button-grid-row-select');
    }
    
    GridSelector.prototype.findSelected = function() {
        return this.grid.find('.button-grid-row-select:checked');
    }
    
    GridSelector.prototype.getSelectedUris = function() {
        return $.map(this.findSelected(), function(el) {
            return $(el).data('target_uri');
        });
    }
    
    GridControl = function(grid, gridSelector) {
        this.gridSelector = gridSelector;
        this.grid = grid;
        this.model = grid.data('model');
        this.refresh();
    }

    $('.grid-table').each(function() {
        var grid = $(this);
        var model = grid.data('model');
        var gs = new GridSelector(grid);
        gs.init();
        
        grid.findRow = function(uri) {
            return grid.find('tr[data-uri="'+uri+'"]');
        }
        grid.removeRow = function (uri) {
            grid.findRow(uri).remove();
            grid.trigger('grid.row.delete', [uri]);
        }
        
        var toggleRow = function (row, toggle) {
            row.toggleClass('warning', toggle);
        }
        grid.on('grid.row.select', function(event, uri) {
            toggleRow(grid.findRow(uri), true);
        });
        grid.on('grid.row.deselect', function(event, uri) {
            toggleRow(grid.findRow(uri), false);
        });
        
        grid.findSelected().each(function(index, row) {
            toggleRow($(this).closest('tr'), $(this).attr(':checked'));
        })
        
        $('.grid-control-bulk').each(function() {
            var $this = $(this);
            var toggleControl = function() {
                $this.toggle(grid.findSelected().length > 0);
            }
            grid.on('grid.row.select grid.row.deselect grid.row.delete', toggleControl);
            toggleControl();
            
            $(this).find('.grid-control-bulk-delete').click(function() {

                if (!window.confirm($(this).attr('data-title')))
                    return false;

                var uris = gs.getSelectedUris();

                $.ajax($(this).data('url'), {
                    'data': { 'uris': uris },
                    'dataType': 'json',
                    'method': 'delete'
                }).fail(function () {
                    
                });
                uris.map(function(uri) { grid.removeRow(uri) });
            })
        })
        
        $(this).on('grid.row.delete', function(event, uri) {
            grid.find('tr[data-uri="' + uri + '"]').remove();
        });
    });
    
    $('body').on('click', '.btn-delete', function() {
        return window.confirm('Delete record - are you sure?');
    });
    
    // each lookup control is assigned unique index (for lookups later added to dom)
    var lookupIndex = 0;
    
    $('body').on('click', '.grid-lookup', function(ev) {
        ev.preventDefault();
        var $modal = $($(this).data('modal'));
        var lookupTarget = $(this).data('lookup-uri');
        /*
        if(typeof ($this).data('lookup') == 'undefined')
            ($this).data('lookup', 'lookup_' + lookupIndex)
        lookupIndex++;
       */
        // is modal already loaded for this lookup
        if ($modal.data('lookup-uri')===lookupTarget)
            return $modal.modal('show');
        
        $modal.find('.modal-body').hide();
        $modal.find('.modal-loading').show();
        
        $modal.modal('show');
        $modal.data('lookup-uri', lookupTarget);
        
        jQuery.get($(this).attr('href'))
            .done(function(res) {
                $modal.find('.modal-body').html(res).show();
                $modal.find('.modal-loading').hide();
            })
    });
    
    $('#grid-modal').on('click', 'tr', function(event) {
        var selectedUri = $(this).data('uri');
        var grid = event.delegateTarget;
        var lookupTarget = $(grid).data('lookup-uri');
        
        $(lookupTarget).val(selectedUri);
        $(grid).modal('hide');
    })
    
    $('#grid-modal').on('click', 'a', function(event) {
        event.preventDefault();
        $.get($(this).attr('href')).done(function(response) {
            $('#grid-modal .modal-body').hide().html(response).show();
        })
    });
    /*
    $('#grid-modal').on('submit', 'form', function(event) {
        event.preventDefault();
    // @todo ajaxify form
        console.log()
    });
    */
});