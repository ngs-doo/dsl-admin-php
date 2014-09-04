FormEntity = function (container) {
        var self = this;

        this.container = $(container);
        this.proto = this.container.attr ("data-prototype");
        this.protoName = this.container.attr ("data-prototype-name");
        this.protoFullname = this.container.attr ("data-prototype-fullname");
        this.name = this.container.attr ("data-name");
        this.list = this.container.children ('ul');
        this.addButton = this.container.children ('a.add');

        this.addButton.unbind('click').click (function () {
            self.addAction();
        });

        this.list.find('.button-remove').unbind('click').click(function () {
            self.removeAction(this);
        });

        return this;
};

FormEntity.prototype.addAction = function () {
    var self = this;
    var proto = this.proto.replace(this.protoName, this.list.children().length, 'g');
    var list = $("<li></li>").appendTo(this.list).append(proto);
    list.find('.button-remove').each (function () {
        $(this).unbind('click').click (function () {
                self.removeAction (this);
        });
    });

    FormEntity.loader(list);
};

FormEntity.prototype.removeAction = function (element) {
    var self = this;
    $(element).closest('li').remove();

    this.list.children().each (function (i) {	
        $(this).find ("[name]").each (function () {
            var field = $(this).attr("name").match (/\[([^\]]+)\]$/);
            var name = self.protoFullname.replace (self.protoName, i, 'g');
            $(this).attr ("name", name + '[' + field[1] + ']');
        });
    });
};

FormEntity._gc = [ ];
FormEntity.loader = function (container) {
        if (!container)
            container = $(document.body);

        container.find ('[data-prototype]').each (function () {
                FormEntity._gc.push (new FormEntity ($(this)));
        });
};
