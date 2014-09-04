<?php
namespace PhpDslAdmin;

use NGS\Client\CrudProxy;
use NGS\Client\DomainProxy;
use NGS\Client\Exception\InvalidRequestException;
use NGS\Client\StandardProxy;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class CrudController // implements \Silex\ControllerProviderInterface
{
    private $app;

    public function __construct(\Silex\Application $app)
    {
        $this->app = $app;
    }

    protected function path($route, $parameters = array())
    {
        return $this->app['url_generator']->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_PATH);
    }

    public function createAction($model)
    {
        $app = $this->app;
        $formName = str_replace('.', '_', $model);
        $form = $app['form.factory']->createBuilder($formName)
            ->setAction($app['request']->getUri())
            ->getForm();

        $form->handleRequest($app['request']);

        if ($form->isValid()) {
            $item = $form->getData();
            $crudProxy = new CrudProxy($app['dsl.client']);

            try {
                $crudProxy->create($item);
                $app['message']->info('Created object "' . $model . '" with URI "' . $item->URI . '"');
                return $app->redirect($this->path('ui_grid', array('model' => $model)));
            } catch (InvalidRequestException $e) {
                $app['message']->error($e->getMessage());
            }
        }

        return $app['twig']->render('model/new.twig', array('form' => $form->createView()));
    }

    public function updateAction($model, $uri)
    {
        $app = $this->app;
        $modelClass = $app['dsl']->resolveClass($model);
        $crudProxy = new CrudProxy($app['dsl.client']);
        $formName = str_replace('.', '_', $model);
        $form = $app['form.factory']->createBuilder($formName)
            ->setMethod('PUT')
            ->setAction($app['request']->getUri())
            ->getForm();
        try {
            $currentItem = $crudProxy->read($modelClass, $uri);
            $form->setData($currentItem);
            $form->handleRequest($app['request']);
        }
        catch (InvalidArgumentException $ex) {
            $app['message']->warn($ex->getMessage());
            $app['logger']->warn($form->getErrorsAsString());
        }
        if ($form->isValid()) {
            $item = $form->getData();
            $crudProxy->update($item);
            $app['message']->info('Updated object "'.$model.'" with URI "'.$item->URI.'"');
            return $app->redirect($this->path('ui_grid', array('model' => $model)));
        }
        $app['logger']->warn($form->getErrorsAsString());
        $app['message']->warn('Submitted data was invalid');
        return $app['twig']->render('model/new.twig', array('form' => $form->createView()));
    }

    public function indexAction($model)
    {
        $app = $this->app;
        $class = $app['dsl']->resolveClass($model);
        $proxy = new DomainProxy($app['dsl.client']);

        $limit = $app['request']->get('limit');
        // $limit = $app['session']->get('grid.limit');
        if ($limit === null)
            $limit = 10;
        $page = $app['request']->get('page') ? : 1;
        $offset = $limit * $page;

        $orderField = $app['request']->get('order');
        $orderDir = $app['request']->get('order_dir') === 'asc' ? : false;
        $order = $orderField ? [$orderField => $orderDir] : [];

        $count = $proxy->count($class);
        $paginator = new \NGS\Symfony\Util\Paginator($count, $page, $limit);

        $linkArgs = ['model' => $model, 'page' => '_page_'];
        if ($orderField) {
            $linkArgs['order'] = $orderField;
            $linkArgs['order_dir'] = $orderDir;
        }
        $pageLinkTemplate = $this->path('ui_grid', array_merge($linkArgs,
            ['page' => '_page_', 'limit' => $limit]));
        $perpageLinkTemplate = $this->path('ui_grid', array_merge($linkArgs,
            ['page' => 1, 'limit' => '_limit_']));

        $maxPageLinks = 9;
        $minPage = $page - floor(($maxPageLinks - 1) / 2);
        $maxPage = $page + floor(($maxPageLinks) / 2);

        if ($minPage < 1) {
            $minPage = 1;
        }
        if ($maxPage > $paginator->getTotalPages()) {
            $maxPage = $paginator->getTotalPages();
        }
        $pageLinks = [];
        for ($i = $minPage; $i <= $maxPage; $i++) {
            $pageLinks[$i] = str_replace('_page_', $i, $pageLinkTemplate);
        }

        $perpageOptions = [10, 20, 50, 100, 200, 500];
        $perpageLinks = [];
        foreach ($perpageOptions as $opt) {
            $perpageLinks[$opt] = str_replace('_limit_', $opt, $perpageLinkTemplate);
        }

        $items = $proxy->search($class, $limit, $paginator->getOffset(), $order);

        $template = $app['request']->isXmlHttpRequest()
            ? $model . '/grid_lookup.twig'
            : $model . '/grid.twig';

        return $app['twig']->render($model . '/grid.twig', [
            //return $app['twig']->render('ui/grid/aggregate.twig', [
            'order' => ['field' => $orderField, 'dir' => $orderDir],
            'items' => $items,
            'model' => $model,
            'count' => $count,
            'paginator' => $paginator,
            'page_links' => $pageLinks,
            'perpage_links' => $perpageLinks,
        ]);
    }

    public function addAction($model)
    {
        $app = $this->app;
        $formName = str_replace('.', '_', $model);
        $form = $app['form.factory']->createBuilder($formName)
            ->setMethod('POST')
            ->setAction($this->path('ui_model_create', array('model' => $model)))
            ->getForm();

        $class = $app['dsl']->resolveClass($model);
        $item = new $class();
        $form->setData($item);

        return $app['twig']->render('model/new.twig', array(
            'form' => $form->createView(),
            'model' => $model,
        ));
    }

    public function editAction($model, $uri)
    {
        $app = $this->app;

        $formName = str_replace('.', '_', $model);
        $crudProxy = new CrudProxy($app['dsl.client']);

        $formBuilder = $app['form.factory']->createBuilder($formName)
            ->setAction('/' . $model . '/' . $uri)
            ->setMethod($uri === null ? 'POST' : 'PUT');

        $form = $formBuilder
            ->setAction($this->path('ui_model_update',
                array('model' => $model, 'uri' => $uri)))
            ->getForm();

        $modelClass = $app['dsl']->resolveClass($model);
        $item = $crudProxy->read($modelClass, $uri);
        $form->setData($item);

        return $app['twig']->render('model/edit.twig', array(
            'item' => $item,
            'form' => $form->createView(),
            'model' => $model,
        ));
    }

    public function deleteAction($model, $uri)
    {
        $app = $this->app;
        $modelClass = $app['dsl']->resolveClass($model);
        $crudProxy = new CrudProxy($app['dsl.client']);
        $item = $crudProxy->read($modelClass, $uri);
        try {
            $crudProxy->delete($modelClass, $item->URI);
            $app['message']->info('Record "' . $model . '" with URI "' . $uri . '" removed');
        } catch (Exception $ex) {
            $app['message']->warn(sprintf('Cannot delete object "%s" with URI %s: %s', $model, $uri, $ex->getMessage()));
        }
        return $app->redirect($this->path('ui_grid', array('model' => $model)));
    }

    public function bulkDeleteAction($model)
    {
        $app = $this->app;
        $modelClass = $app['dsl']->resolveClass($model);
        $uris = $app['request']->get('uris');
        $domainProxy = new DomainProxy($app['dsl.client']);
        $items = $domainProxy->find($modelClass, $uris);
        try {
            $proxy = new StandardProxy($app['dsl.client']);
            $proxy->delete($items);
            //$app['message']->info('Deleted '.count($items).' "'.$model.'" object(s)');
            return $app->json($uris);
        } catch (Exception $ex) {
            $app['message']->warn(sprintf('Could not bulk delete objects "%s": %s', $model, $ex->getMessage()));
            return $app->json($uris, 403);
        }
        return $app->json($uris);
    }
}
