<?php

/*
 * This source file is available under two different licenses:
 *   - GNU General Public License version 3 (GPLv3)
 *   - DACHCOM Commercial License (DCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) DACHCOM.DIGITAL AG (https://www.dachcom-digital.com)
 * @license    GPLv3 and DCL
 */

namespace DynamicSearchBundle\Controller;

use DynamicSearchBundle\Configuration\ConfigurationInterface;
use DynamicSearchBundle\Form\Type\SearchFormType;
use DynamicSearchBundle\OutputChannel\Result\MultiOutputChannelResultInterface;
use DynamicSearchBundle\OutputChannel\Result\OutputChannelArrayResultInterface;
use DynamicSearchBundle\OutputChannel\Result\OutputChannelPaginatorResultInterface;
use DynamicSearchBundle\OutputChannel\Result\OutputChannelResultInterface;
use DynamicSearchBundle\Processor\OutputChannelProcessorInterface;
use Pimcore\Controller\FrontendController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SearchFrontendController extends FrontendController
{
    const SUMMARY_LENGTH = 255;

    public function __construct(
        protected FormFactoryInterface $formFactory,
        protected ConfigurationInterface $configuration,
        protected OutputChannelProcessorInterface $outputChannelWorkflowProcessor
    ) {
    }

    /**
     * @throws NotFoundHttpException
     */
    public function searchAction(Request $request, string $contextName, string $outputChannelName): Response
    {
        $outputChannelName = str_replace('-', '_', $outputChannelName);

        if (!$this->outputChannelExists($contextName, $outputChannelName)) {
            throw $this->createNotFoundException(sprintf('invalid, internal or no frontend output channel "%s".', $outputChannelName));
        }

        return $this->renderFrontendSearch($request, $outputChannelName, $contextName, $this->getOutputChannelView($contextName, $outputChannelName, 'list'));
    }

    /**
     * @throws NotFoundHttpException
     */
    public function multiSearchAction(Request $request, string $contextName, string $outputChannelName): Response
    {
        $outputChannelName = str_replace('-', '_', $outputChannelName);

        if (!$this->outputChannelExists($contextName, $outputChannelName, true)) {
            throw $this->createNotFoundException(sprintf('invalid, internal or no frontend output channel "%s".', $outputChannelName));
        }

        return $this->renderFrontendSearch($request, $outputChannelName, $contextName, $this->getOutputChannelView($contextName, $outputChannelName, 'multi-list'));
    }

    protected function renderFrontendSearch(Request $request, string $outputChannelName, string $contextName, string $viewName): Response
    {
        $hasError = false;
        $errorMessage = null;
        $outputChannelResult = null;
        $searchActive = false;

        $form = $this->formFactory->createNamed('', SearchFormType::class, null, ['method' => 'GET']);

        $form = $form->handleRequest($request);

        if ($form->isSubmitted()) {
            try {
                $searchActive = true;
                $outputChannelResult = $this->outputChannelWorkflowProcessor->dispatchOutputChannelQuery($contextName, $outputChannelName);
//                dd($outputChannelResult);
            } catch (\Throwable $e) {
                $hasError = true;
                $errorMessage = sprintf(
                    'Error while loading search output channel "%s" for "%s" context. Error was: %s',
                    $outputChannelName,
                    $contextName,
                    $e->getMessage()
                );
            }
        }

        $viewName = sprintf('@DynamicSearch/output-channel/%s/list.html.twig', $viewName);

        if ($hasError === true) {
            return $this->renderTemplate($viewName, [
                'has_error'     => $hasError,
                'error_message' => $errorMessage
            ]);
        }

        if ($searchActive === false) {
            return $this->renderTemplate($viewName, [
                'has_error'     => $hasError,
                'error_message' => $errorMessage,
                'search_active' => $searchActive,
                'form'          => $form->createView(),
            ]);
        }

        $runtimeQueryProvider = null;
        $routeName = null;
        if ($outputChannelResult instanceof MultiOutputChannelResultInterface) {
            $routeName = 'dynamic_search_frontend_multi_search_list';
            $runtimeQueryProvider = $outputChannelResult->getRuntimeQueryProvider();
        } elseif ($outputChannelResult instanceof OutputChannelResultInterface) {
            $routeName = 'dynamic_search_frontend_search_list';
            $runtimeQueryProvider = $outputChannelResult->getRuntimeQueryProvider();
        }

        if ($runtimeQueryProvider === null) {
            return $this->renderTemplate($viewName, [
                'has_error'     => true,
                'error_message' => sprintf(
                    'output channel result "%s" needs to be instance of "%s" or "%s".',
                    $outputChannelName,
                    MultiOutputChannelResultInterface::class,
                    OutputChannelResultInterface::class
                )
            ]);
        }

        $params = [
            'has_error'         => false,
            'error_message'     => null,
            'search_active'     => $searchActive,
            'form'              => $form->createView(),
            'user_query'        => $runtimeQueryProvider->getUserQuery(),
            'query_identifier'  => $runtimeQueryProvider->getQueryIdentifier(),
            'search_route_name' => $routeName,
            'context_name'      => $contextName
        ];

        if ($outputChannelResult instanceof OutputChannelResultInterface) {
            $mergedParams = array_merge($params, $this->prepareQueryVars($outputChannelResult));

            $additionalInformation = [];

            $searchQuery = $this->cleanRequestString($request->get('q'));

            if (!empty($searchQuery)) {
                $query = $this->cleanTerm($searchQuery);
            }

            foreach ($mergedParams['paginator'] as $key => $value) {
                $additionalInformation[$key]['summary'] = $this->getSummaryForUrl($value['full_content'], $query);
                $additionalInformation[$key]['description'] = $this->getSummaryForUrl($value['description'], $query);
            }

            return $this->renderTemplate($viewName, array_merge($params, array_merge($mergedParams, ['additional_information' => $additionalInformation])));
        }

        $blocks = [];
        if ($outputChannelResult instanceof MultiOutputChannelResultInterface) {
            foreach ($outputChannelResult->getResults() as $resultBlockIdentifier => $resultBlock) {
                $blocks[$resultBlockIdentifier] = $this->prepareQueryVars($resultBlock);
            }
        }

        //FF, here output for SearchFrontendController
        return $this->renderTemplate($viewName, array_merge($params, ['blocks' => $blocks]));
    }

    protected function prepareQueryVars(OutputChannelResultInterface $outputChannelResult): array
    {
        $data = null;
        $paginator = null;

        if ($outputChannelResult instanceof OutputChannelPaginatorResultInterface) {
            $paginator = $outputChannelResult->getPaginator();
        } elseif ($outputChannelResult instanceof OutputChannelArrayResultInterface) {
            $data = $outputChannelResult->getResult();
        }

        $runtimeOptions = $outputChannelResult->getRuntimeOptions();

        return [
            'data'            => $data,
            'paginator'       => $paginator,
            'current_page'    => $runtimeOptions['current_page'],
            'page_identifier' => $runtimeOptions['page_identifier'],
            'total_count'     => $outputChannelResult->getHitCount(),
            'filter'          => $outputChannelResult->getFilter(),
            'oc_allocator'    => $outputChannelResult->getOutputChannelAllocator(),
        ];
    }

    protected function outputChannelExists(string $contextName, string $outputChannelName, bool $multiSearchOnly = false): bool
    {
        $channelConfig = $this->getOutputChannelConfig($contextName, $outputChannelName);

        if (!is_array($channelConfig)) {
            return false;
        }

        if ($channelConfig['internal'] === true) {
            return false;
        }

        if ($multiSearchOnly === true && $channelConfig['multiple'] !== true) {
            return false;
        }

        if ($multiSearchOnly === false && $channelConfig['multiple'] === true) {
            return false;
        }

        return $channelConfig['use_frontend_controller'] === true;
    }

    protected function getOutputChannelView(string $contextName, string $outputChannelName, string $default): string
    {
        $channelConfig = $this->getOutputChannelConfig($contextName, $outputChannelName);

        if (!is_array($channelConfig)) {
            return $default;
        }

        return isset($channelConfig['view_name']) && is_string($channelConfig['view_name']) ? $channelConfig['view_name'] : $default;
    }

    protected function getOutputChannelConfig(string $contextName, string $outputChannelName): ?array
    {
        $contextConfig = $this->getParameter('dynamic_search.context.full_configuration');

        if (!isset($contextConfig[$contextName])) {
            return null;
        }

        if (!array_key_exists($outputChannelName, $contextConfig[$contextName]['output_channels'])) {
            return null;
        }

        return $contextConfig[$contextName]['output_channels'][$outputChannelName];
    }

    /**
     * @param $content
     * @param $queryStr
     *
     * @return mixed|string
     */
    public function getSummaryForUrl($content, $queryStr)
    {
        $queryElements = explode(' ', $queryStr);

        //remove additional whitespaces
        $content = preg_replace('/[\s]+/', ' ', $content);

        $summary = $this->getHighlightedSummary($content, $queryElements);

        if ($summary === false) {
            return substr($content, 0, self::SUMMARY_LENGTH);
        }

        return $summary;
    }

    /**
     * finds the query strings position in the text
     *
     * @param  string $text
     * @param  string $queryStr
     *
     * @return int
     */
    protected function findPosInSummary($text, $queryStr)
    {
        $pos = stripos($text, ' ' . $queryStr . ' ');
        if ($pos === false) {
            $pos = stripos($text, '"' . $queryStr . '"');
        }
        if ($pos === false) {
            $pos = stripos($text, '"' . $queryStr . '"');
        }
        if ($pos === false) {
            $pos = stripos($text, ' ' . $queryStr . '-');
        }
        if ($pos === false) {
            $pos = stripos($text, '-' . $queryStr . ' ');
        }
        if ($pos === false) {
            $pos = stripos($text, $queryStr . ' ');
        }
        if ($pos === false) {
            $pos = stripos($text, ' ' . $queryStr);
        }
        if ($pos === false) {
            $pos = stripos($text, $queryStr);
        }

        return $pos;
    }

    /**
     * extracts summary with highlighted search word from source text
     *
     * @param string   $text
     * @param string[] $queryTokens
     *
     * @return string
     */
    protected function getHighlightedSummary($text, $queryTokens)
    {
        $pos = false;
        $tokenInUse = $queryTokens[0];

        foreach ($queryTokens as $queryStr) {
            $tokenInUse = $queryStr;
            $pos = $this->findPosInSummary($text, $queryStr);

            if ($pos !== false) {
                break;
            }
        }

        if ($pos !== false) {
            $start = $pos - 100;

            if ($start < 0) {
                $start = 0;
            }

            $summary = substr($text, $start, self::SUMMARY_LENGTH + strlen($tokenInUse));
            $summary = trim($summary);

            $tokens = explode(' ', $summary);

            if (strtolower($tokens[0]) != strtolower($tokenInUse)) {
                $tokens = array_slice($tokens, 1, -1);
            } else {
                $tokens = array_slice($tokens, 0, -1);
            }

            $trimmedSummary = implode(' ', $tokens);

            foreach ($queryTokens as $queryStr) {
                $trimmedSummary = preg_replace('@([ \'")(-:.,;])(' . $queryStr . ')([ \'")(-:.,;])@si',
                    " <span class=\"highlight\">\\1\\2\\3</span>", $trimmedSummary);
                $trimmedSummary = preg_replace('@^(' . $queryStr . ')([ \'")(-:.,;])@si',
                    " <span class=\"highlight\">\\1\\2</span>", $trimmedSummary);
                $trimmedSummary = preg_replace('@([ \'")(-:.,;])(' . $queryStr . ')$@si',
                    " <span class=\"highlight\">\\1\\2</span>", $trimmedSummary);
            }

            return empty($trimmedSummary) ? false : $trimmedSummary;
        }

        return false;
    }

    /**
     * remove evil stuff from request string
     *
     * @param  string $requestString
     *
     * @return string
     */
    public function cleanRequestString($requestString)
    {
        $queryFromRequest = strip_tags(urldecode($requestString));
        $queryFromRequest = str_replace(['<', '>', '"', "'", '&'], '', $queryFromRequest);

        return $queryFromRequest;
    }

    /**
     * @param $term
     *
     * @return string
     */
    public function cleanTerm($term)
    {
        return trim(
            preg_replace('|\s{2,}|', ' ',
                preg_replace('|[^\p{L}\p{N} ]/u|', ' ',
                    strtolower(
                        strip_tags(
                            str_replace(["\n", '<'], [' ', ' <'], $term)
                        )
                    )
                )
            )
        );
    }
}
