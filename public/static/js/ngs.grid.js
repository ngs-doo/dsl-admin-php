if (typeof NGS == 'undefined') var NGS = { };

NGS.modal = function (parent) {
    console.warn('modal')
    var e = $('<div tabIndex="-1" class="modal fade" role="dialog" aria-hidden="true">').appendTo ('body');
//    var e = $('.grid-modal-container>.modal');
    console.warn(e);
    
    $.extend (e, NGS.modal.prototype);

    if (parent)
        e.modalParent = parent;
    else
        e.modalParent = NGS.modal.modalParent;

    e.on ('hidden', function () {
        if (e.dont)
            return e.dont = false;

        NGS.modal.modalParent = e.modalParent;

        if (e.modalParent) 
            e.modalParent.modal ('show');
    });

    return e;
};

NGS.modal.modalParent = null;

NGS.modal.prototype.isEmpty = function () {
    return this.is (':empty');
};

NGS.modal.prototype.show = function () {
    NGS.modal.modalParent = this;
    if (this.modalParent) {
        this.modalParent.dont = true;
        this.modalParent.modal('hide');
    }

    this.modal ('show');
};

NGS.modal.prototype.hide = function () {
    this.modal ('hide'); 
};

NGS.embeddedModal = function (grid, container, parent) {
    var c = $('<div class="hide">').appendTo ($(container));
    var e = $('<div>').appendTo (c);

    $.extend (e, NGS.embeddedModal.prototype);
    e.container = c;
    e.grid = grid;

    var back = $('<button class="btn btn-danger"></button>').html ('Back');
    back.click (function (event) {
        e.hide(); 
    });

    var unlock = $('<button class="btn"></button>').html ('Unlock');
    unlock.click (function (event) {
        var p = c.parent();
        if (p.css ('position') == "fixed") {
            p.css ('position', '');
            event.target.textContent = 'Lock';
            document.body.scrollIntoView();
        } else {
            p.css ('position', 'fixed');
            event.target.textContent = 'Unlock';
        }
    });
    c.prepend (unlock);
    c.prepend (back);

    e.isRoot = grid.parents(container).length === 0;

    if (parent)
        e.modalParent = parent;
    else if (!e.isRoot)
        e.modalParent = NGS.embeddedModal.activeModal;

    return e;
}

NGS.embeddedModal.activeModal = null;

NGS.embeddedModal.prototype.isEmpty = function () {
    return this.is (':empty');
};

NGS.embeddedModal.prototype.show = function () {
    if (NGS.embeddedModal.activeModal) {
        NGS.embeddedModal.activeModal.hide (true);
    }

    NGS.embeddedModal.activeModal = this;

    this.container.parent().css ('position', 'fixed');
    this.container.removeClass ('hide');

    if (this.isRoot) {
        $('a[lookup-modal]').removeClass ('btn-danger');
        this.grid.addClass ('btn-danger');
    }
}

NGS.embeddedModal.prototype.hide = function (dontShow) {
    if (this.modalParent && !dontShow)
        this.modalParent.show();

    this.container.addClass ('hide');
    if (this.isRoot && !this.modalParent)
        this.grid.removeClass ('btn-danger');
}

NGS.grid = function (element) {
    var self = this;
    
    this.element = $(element);
    this.targetElement = this.element.parent().find (this.element.attr ('lookup-target'));
    this.displayElement = this.element.parent().find (this.element.attr ('lookup-display-target'));
    this.displayValue = this.element.attr ('lookup-display');
    this.modalContainer = this.element.attr ('lookup-modal') || 'body';
    this.url = this.element.attr('href');
    
   //  console.warn(this.modalContainer);

    if (this.modalContainer == 'body' || $(this.modalContainer).length == 0) {
        this.modal = new NGS.modal ();
        this.addModal = new NGS.modal (this.modal);
    } else {
        this.modal = new NGS.embeddedModal (this.element, this.modalContainer);
        this.addModal = new NGS.embeddedModal(this.element, this.modalContainer, this.modal);
    }

    self.modal.show();
    self.element.unbind ('click').click (function (event) {
        event.preventDefault();
        self.modal.show();
    });
    
    if (self.modal.empty()) {
        $.get (self.url, function (response) {
            console.warn(self.modal);
            console.warn(self.modal.find('.modal-body'));
            self.modal.find('.modal-body').html (response);
            self.init();
            self.modal.on ('click', 'tr', function () {
                var key = $(this).find('.key').html();
                var display = $(this).find('.display').html();

                self.targetElement.val(key);
                var event = document.createEvent ('Event');
                event.initEvent ('change', true, true);
                self.targetElement.get(0).dispatchEvent (event);

                if (display)
                    self.displayElement.val(display);
                else
                    self.displayElement.val(key);

                self.targetElement.trigger ('change');
                self.modal.hide();
            });
        }, 'html');
    }
};

NGS.grid.prototype.init = function () {
    var self = this;

    var $grid = self.modal;
    this.ajaxEdit();

    var editContent = function(data) {
        var content = $(data).find('.grid-content');
        var rows = content.find('tbody tr').css('cursor', 'pointer');
        // hide actions columns
        content.find('thead th:last').hide();
        rows.find('td:last').hide();
        return content;
    };

    var searchForm = $grid.find ('.search form');
    if (searchForm.length > 0) {
        searchForm.submit(function(event) {
            event.preventDefault();
            var $form = $(this);
            var $submitButton = $form.find('[type=submit]').addClass('disabled');

            $.get(
                $form.attr('action'),
                // @todo remove limit
                $form.serialize()+'&_pagination=off',
                function (data) {
                    var content = editContent (data);
                    $grid.find('.grid-content').replaceWith(content);
                    $submitButton.removeClass('disabled');
                },
                'html'
            );
        });
    };

    editContent ($grid);

    $grid.find('.clear-search').click(function(event){
        event.preventDefault();
        $grid.find('.search input').val('');
        $grid.find('.grid-content').html('');
    })
};

NGS.grid.prototype.ajaxEdit = function () {
    var self = this;

    this.modal.find('.action-add').each(function() {
        $(this).click(function(event) {
            event.preventDefault();
            var $this = $(this);
            var url = $this.attr('href');
            
            console.log('ajaxedit');
            
            $.get(
                url,
                null,
                function(response) {
                    if (self.addModal.empty()) {
                        self.addModal.html (response);
                        self.ajaxForm();
                    }

                    self.addModal.show();
                },
                'html'
            );
         }) ;
     });
}

NGS.grid.prototype.ajaxForm = function() {
    var self = this;

    this.addModal.find('form').each(function() {
        var $form = $(this);
        
        $form.find('.cancel').click(function(event) {
            event.preventDefault();
            self.addModal.hide();
        });
        
        $form.submit(function(event) {
            event.preventDefault();
            $.post(
                $form.attr('action'),
                $form.serialize(),
                function(response) {
                    self.addModal.hide();
                    self.modal.hide();

                    if(typeof(response.data.item)!=='undefined') {
                        self.targetElement.val (response.data.item.URI);

                        // inform the DOM we changed element's value
                        var event = document.createEvent ('Event');
                        event.initEvent ('change', true, true);
                        self.targetElement.get(0).dispatchEvent (event);
                        

                        var display = response.data.item[self.displayValue];
                        if (display)
                            self.displayElement.val (display);
                        else
                            self.displayElement.val (response.data.item.URI);
                    }
                },
                'json'
            );
        });
    });
}

NGS.grid.loader = function () {
    $('body').on ('click', '.grid-lookup', function (event) {
        event.preventDefault();
        $(this).removeClass ('grid-lookup');
        var grid = new NGS.grid (this);
    });
};
