<?php
namespace PhpDslAdmin;

use Silex\Application;

class CrudControllerProvider implements \Silex\ControllerProviderInterface
{
    /**
     * Returns routes to connect to the given application.
     *
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(Application $app)
    {
        $crud = $app['controllers_factory'];
        $crud->get('/{model}', 'crud.controller:indexAction')->bind('ui_grid');
        $crud->get('/{model}/', 'crud.controller:addAction')->bind('ui_model_new');
        $crud->get('/{model}/{property}/download/{uri}', 'crud.controller:downloadAction')->bind('ui_model_download')->assert('uri', '.+');
        $crud->get('/{model}/{uri}', 'crud.controller:editAction')->bind('ui_model_edit')->assert('uri', '.*');
        $crud->post('/{model}', 'crud.controller:createAction')->bind('ui_model_create');
        $crud->put('/{model}/{uri}', 'crud.controller:updateAction')->bind('ui_model_update')->assert('uri', '.+');
        $crud->delete('/{model}/{uri}', 'crud.controller:deleteAction')->bind('ui_model_delete')->assert('uri', '.+');;
        $crud->delete('/{model}', 'crud.controller:bulkDeleteAction')->bind('ui_bulk_delete');

        return $crud;
    }
}