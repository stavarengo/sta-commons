<?php
/**
 * irmo Project ${PROJECT_URL}
 *
 * @link      ${GITHUB_URL} Source code
 */

namespace Sta\Commons;

/**
 * Class InternalRoute
 * @package Web\Controller\Action\Facebook
 */
class InternalRoute extends StdClass
{
    /**
     * Nome da rota para onde será redirecionado.
     * É o mesmo valor que você passaria para o parâmetro "$route" do método {@link \Zend\Mvc\Controller\Plugin\Url::fromRoute()}
     * @var string
     */
    protected $route = null;
    /**
     * Opcional. Parametros para construção da rota.
     * É o mesmo valor que você passaria para o parâmetro "$params" do método {@link \Zend\Mvc\Controller\Plugin\Url::fromRoute()}
     * @var array
     */
    protected $params = array();
    /**
     * É o mesmo valor que você passaria para o parâmetro "$options" do método {@link \Zend\Mvc\Controller\Plugin\Url::fromRoute()}
     * @var array
     */
    protected $options = array();
    
    public static function fromRoute(\Zend\Mvc\Router\RouteMatch $route) 
    {
        $internalRoute = new self();
        $internalRoute->setRoute($route->getMatchedRouteName());
        $internalRoute->setParams($route->getParams());
        if ($_GET) {
            $internalRoute->setOptions(
                array(
                    'query' => $_GET,
                )
            );
        }
        
        return $internalRoute;
    }

    public static function fromRouteData($route, array $params = array(), array $options = array())
    {
        $internalRoute = new self();
        $internalRoute->setRoute($route);
        $internalRoute->setParams($params);
        $internalRoute->setOptions($options);

        return $internalRoute;
    }
    
    /**
     * @param array $options
     * @return $this;
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $params
     * @return $this;
     */
    public function setParams(array $params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param string $route
     * @return $this;
     */
    public function setRoute($route)
    {
        $this->route = $route;

        return $this;
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }
} 
