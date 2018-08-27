<?php

namespace Aakron\Bundle\ProductBundle\Autocomplete;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Search\ProductRepository as ProductSearchRepository;
use Lsw\ApiCallerBundle\Call\HttpGetJson as HttpGetJson;
class ProductVisibilityLimitedSearchHandler extends SearchHandler
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var ProductRepository */
    protected $entityRepository;

    /** @var ProductManager */
    protected $productManager;

    /** @var FrontendHelper */
    protected $frontendHelper;

    /** @var \Oro\Bundle\ProductBundle\Search\ProductRepository */
    protected $searchRepository;

    /**
     * @param string         $entityName
     * @param array          $properties
     * @param RequestStack   $requestStack
     * @param ProductManager $productManager
     */
    public function __construct(
        $entityName,
        array $properties,
        RequestStack $requestStack,
        ProductManager $productManager,
        $container
    ) {
        $this->requestStack   = $requestStack;
        $this->productManager = $productManager;
        $this->container = $container->getContainer();
        parent::__construct($entityName, $properties);
    }

    /**
     * {@inheritdoc}
     */
    protected function checkAllDependenciesInjected()
    {
        if (!$this->entityRepository || !$this->idFieldName) {
            throw new \RuntimeException('Search handler is not fully configured');
        }
    }

    /**
     * @param FrontendHelper $frontendHelper
     */
    public function setFrontendHelper(FrontendHelper $frontendHelper)
    {
        $this->frontendHelper = $frontendHelper;
    }

    /**
     * @param \Oro\Bundle\ProductBundle\Search\ProductRepository $searchRepository
     */
    public function setSearchRepository(ProductSearchRepository $searchRepository)
    {
        $this->searchRepository = $searchRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($item)
    {
        $result = [];

        if ($this->idFieldName) {
            $result[$this->idFieldName] = $this->getPropertyValue($this->idFieldName, $item);
        }

        foreach ($this->getProperties() as $destinationKey => $property) {
            if ($this->isItem($item)) {
                $result[$property] = $this->getSelectedData($item, $destinationKey);
                continue;
            }
            $result[$property] = $this->getPropertyValue($property, $item);
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        if (!isset($this->properties['orm'])) {
            return $this->properties; // usual case
        }

        $request = $this->requestStack->getCurrentRequest();

        if (null === $this->frontendHelper || (false === $this->frontendHelper->isFrontendRequest($request))) {
            return $this->properties['orm'];
        }

        return $this->properties['search'];
    }

    /**
     * {@inheritdoc}
     */
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request || !$params = $request->get(ProductSelectType::DATA_PARAMETERS)) {
            $params = [];
        }

        if (null === $this->frontendHelper || (false === $this->frontendHelper->isFrontendRequest($request))) {
            return $this->searchEntitiesUsingOrm($search, $firstResult, $maxResults, $params);
        }

        return $this->searchEntitiesUsingIndex($search, $firstResult, $maxResults);
    }

    
    protected function createQuryUrl($search,$start,$rows)
    {
       $solrUrl ='http://www.aakronline.com:8085/solr/aakronline_live_us/select';
       $solrParameter[] ='q=*:*';
       
       
       $solrParameter[]='sort=if(exists(prioritize_supplier_0_i),prioritize_supplier_0_i,99999)+asc,custom_sort_order_i+asc,sku_priority_i+asc,min_price_without_login_1_f+desc';
       $solrParameter[]='start='.$start;
       $solrParameter[]='rows='.$rows;
       $solrParameter[]='fl=id,product_id_i,culture_product_id_i,title_t,description_t,sku_t,slug_t,image_path_t,image_thumb_path_t,min_price_f,min_price_without_login_f,max_price_f,supplier_id_i,line_name_t,line_name_id_i,base_price_f,category_name_ss,catalog_year_t,price_currency_s,price_valid_until_s,supplier_s,post_name_s,pppc_member_number_s,min_quantity_i,max_quantity_i,attribute_ss,score,product_tag_is,mapped_product_id_is,rating_f,currency_name_s,supplier_name_s,public_custom_tag_is,public_custom_tag_ss,private_custom_tag_is,private_custom_tag_ss,custom_tag_style_ss,color_ss,variation_image_path_txt,variation_image_id_ss,color_mapping,color_mapping_search,min_qty_1_i,max_qty_1_i,price_1_f,code_1_s,min_qty_2_i,max_qty_2_i,price_2_f,code_2_s,min_qty_3_i,max_qty_3_i,price_3_f,code_3_s,min_qty_4_i,max_qty_4_i,price_4_f,code_4_s,min_qty_5_i,max_qty_5_i,price_5_f,code_5_s,min_qty_6_i,max_qty_6_i,price_6_f,code_6_s,min_qty_7_i,max_qty_7_i,price_7_f,code_7_s,min_qty_1_1_i,max_qty_1_1_i,price_1_1_f,code_1_1_s,min_qty_1_2_i,max_qty_1_2_i,price_1_2_f,code_1_2_s,min_qty_1_3_i,max_qty_1_3_i,price_1_3_f,code_1_3_s,min_qty_1_4_i,max_qty_1_4_i,price_1_4_f,code_1_4_s,min_qty_1_5_i,max_qty_1_5_i,price_1_5_f,code_1_5_s,min_qty_1_6_i,max_qty_1_6_i,price_1_6_f,code_1_6_s,min_qty_1_7_i,max_qty_1_7_i,price_1_7_f,code_1_7_s,min_price_1_f,min_price_without_login_1_f,max_price_1_f';
       $solrParameter[]='wt=json';
       $solrParameter[]='indent=true';
       $solrParameter[]='defType=edismax';
       $solrParameter[]='pf=title_t+category_name_ss+supplier_s+line_name_t+color_mapping_search';
       $solrParameter[]='ps=0';
       $splitWords = explode(" ",$search);
       $joinBQStringArray =array();
       $joinFQStringArray =array();
       foreach($splitWords as $searchWords)
       {
           $joinBQStringArray[]='title_t:("'.$searchWords.'")^100+AND+keyword_txt:("'.$searchWords.'")^50+AND+color_mapping_search:("'.$searchWords.'")^34';
           $joinFQStringArray[]= '"'.$searchWords.'"';
       }
       $joinBQString = implode("+AND+", $joinBQStringArray);
       $joinFQString = implode("+AND+", $joinFQStringArray);
      
       $solrParameter[]='fq=is_feature_product_t:"no"+-quote_s:yes+AND+(keyword_txt:('.$joinFQString.'))+AND+instance_id_i:1';       
       $solrParameter[]='bq=('.$joinBQString.')';
       $solrParameter[]='stopwords=true';
       $solrParameter[]='lowercaseOperators=true';
       $solrParameter[]='hl=true';
       $solrParameter[]='hl.fl=color_search_image';
       $solrParameter[]='hl.simple.pre=<em>';
       $solrParameter[]='hl.simple.post=</em>';
      
       return $solrUrl."?".implode("&", $solrParameter);
    }
    /**
     * @param $search
     * @param $firstResult
     * @param $maxResults
     * @param $params
     * @return array
     */
    protected function searchEntitiesUsingOrm($search, $firstResult, $maxResults, $params)
    {
       $firstResult = $this->requestStack->getCurrentRequest()->query->get("page") * 10;
       $maxResults = 11;
       if($this->requestStack->getCurrentRequest()->query->get("page")==1)
       {
           $firstResult = $this->requestStack->getCurrentRequest()->query->get("page");
       }
        $apiCallerManager = $this->container->get('api_caller');
        $searchQuery = $this->createQuryUrl($search,$firstResult,$maxResults);
        
        $responseData = $apiCallerManager->call(new HttpGetJson($searchQuery,array()));
        $json  = json_encode($responseData);
        $responseData = json_decode($json, true);
        
        $totalRecordCount = 0;
        $searchResult = array();
        if(isset($responseData["response"]["numFound"]))
        {
            $totalRecordCount = $responseData["response"]["numFound"];
        }
        if(isset($responseData["response"]["docs"]))
        {
            $searchResult = $responseData["response"]["docs"];
        }

        $responseFinalResult = array();
        foreach($searchResult as $key=>$finalResult)
        {
            $finalResult = (array)$finalResult;
            $responseFinalResult[$key]['id'] =$finalResult['product_id_i'];
            $responseFinalResult[$key]['sku'] = $finalResult['sku_t'];           
            $responseFinalResult[$key]['defaultName']['string'] = $finalResult['title_t'];            
        }
       return $responseFinalResult;
    }

    /**
     * @param $search
     * @param $firstResult
     * @param $maxResults
     * @return \Oro\Bundle\SearchBundle\Query\Result\Item[]
     */
    protected function searchEntitiesUsingIndex($search, $firstResult, $maxResults)
    {
        $searchQuery = $this->searchRepository->getSearchQuery($search, $firstResult, $maxResults);

        // Configurable products require additional option selection is not implemented yet
        // Thus we need to hide configurable products from the product drop-downs
        // @TODO remove after configurable products require additional option selection implementation
        $searchQuery->addWhere(
            Criteria::expr()->neq('type', Product::TYPE_CONFIGURABLE)
        );

        $searchQuery->setFirstResult($firstResult);
        $searchQuery->setMaxResults($maxResults);
        $result = $searchQuery->getResult();

        return $result->getElements();
    }

    /**
     * @param Item   $item
     * @param string $property
     * @return null|string
     */
    protected function getSelectedData($item, $property)
    {
        $data = $item->getSelectedData();

        if (empty($data)) {
            return null;
        }

        foreach ($data as $key => $value) {
            if ($key === $property) {
                return (string)$value;
            }

            // support localized properties
            if (strpos($key, $property) === 0) {
                return (string)$value;
            }
        }

        return null;
    }

    /**
     * @param $object
     * @return bool
     */
    protected function isItem($object)
    {
        return is_object($object) && method_exists($object, 'getSelectedData');
    }
}
