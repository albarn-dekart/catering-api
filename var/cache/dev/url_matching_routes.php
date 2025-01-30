<?php

/**
 * This file has been auto-generated
 * by the Symfony Routing Component.
 */

return [
    false, // $matchHost
    [ // $staticRoutes
        '/api/token/refresh' => [[['_route' => 'gesdinet_jwt_refresh_token'], null, null, null, false, false, null]],
        '/api/logout' => [[['_route' => '_logout_api'], null, null, null, false, false, null]],
        '/_profiler' => [[['_route' => '_profiler_home', '_controller' => 'web_profiler.controller.profiler::homeAction'], null, null, null, true, false, null]],
        '/_profiler/search' => [[['_route' => '_profiler_search', '_controller' => 'web_profiler.controller.profiler::searchAction'], null, null, null, false, false, null]],
        '/_profiler/search_bar' => [[['_route' => '_profiler_search_bar', '_controller' => 'web_profiler.controller.profiler::searchBarAction'], null, null, null, false, false, null]],
        '/_profiler/phpinfo' => [[['_route' => '_profiler_phpinfo', '_controller' => 'web_profiler.controller.profiler::phpinfoAction'], null, null, null, false, false, null]],
        '/_profiler/xdebug' => [[['_route' => '_profiler_xdebug', '_controller' => 'web_profiler.controller.profiler::xdebugAction'], null, null, null, false, false, null]],
        '/_profiler/open' => [[['_route' => '_profiler_open_file', '_controller' => 'web_profiler.controller.profiler::openAction'], null, null, null, false, false, null]],
        '/api/meals' => [[['_route' => 'api_get_meals', '_controller' => 'App\\Controller\\MealController::getMeals'], null, ['GET' => 0], null, false, false, null]],
        '/api/categories' => [[['_route' => 'api_get_categories', '_controller' => 'App\\Controller\\MealController::getCategories'], null, ['GET' => 0], null, false, false, null]],
        '/api/meal_plans' => [[['_route' => 'api_get_meals_plans', '_controller' => 'App\\Controller\\MealPlanController::getMealPlans'], null, ['GET' => 0], null, false, false, null]],
        '/api/admin/meal_plans/new' => [[['_route' => 'api_new_meal_plan', '_controller' => 'App\\Controller\\MealPlanController::newMealPlan'], null, ['POST' => 0], null, false, false, null]],
        '/api/user/orders' => [[['_route' => 'api_get_orders', '_controller' => 'App\\Controller\\OrderController::getOrders'], null, ['GET' => 0], null, false, false, null]],
        '/api/order/new' => [[['_route' => 'api_create_order', '_controller' => 'App\\Controller\\OrderController::newOrder'], null, ['POST' => 0], null, false, false, null]],
        '/api/register' => [[['_route' => 'register', '_controller' => 'App\\Controller\\RegisterController::register'], null, ['POST' => 0], null, false, false, null]],
        '/api/admin/users' => [[['_route' => 'api_users', '_controller' => 'App\\Controller\\UserController::getUsers'], null, ['GET' => 0], null, false, false, null]],
        '/api/user' => [
            [['_route' => 'api_user_details', '_controller' => 'App\\Controller\\UserController::user'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_update_details', '_controller' => 'App\\Controller\\UserController::updateDetails'], null, ['PUT' => 0], null, false, false, null],
        ],
        '/api/user/change_password' => [[['_route' => 'api_change_password', '_controller' => 'App\\Controller\\UserController::changePassword'], null, ['PATCH' => 0], null, false, false, null]],
        '/api/login' => [[['_route' => 'api_login'], null, null, null, false, false, null]],
    ],
    [ // $regexpList
        0 => '{^(?'
                .'|/api(?'
                    .'|/(?'
                        .'|\\.well\\-known/genid/([^/]++)(*:46)'
                        .'|errors/(\\d+)(*:65)'
                        .'|validation_errors/([^/]++)(*:98)'
                    .')'
                    .'|(?:/(index)(?:\\.([^/]++))?)?(*:134)'
                    .'|/(?'
                        .'|docs(?:\\.([^/]++))?(*:165)'
                        .'|contexts/([^.]+)(?:\\.(jsonld))?(*:204)'
                        .'|validation_errors/([^/]++)(?'
                            .'|(*:241)'
                        .')'
                        .'|images/([^/]++)(*:265)'
                        .'|meals/([^/]++)(*:287)'
                        .'|admin/(?'
                            .'|meal_plans/([^/]++)/(?'
                                .'|update(*:333)'
                                .'|delete(*:347)'
                            .')'
                            .'|user/([^/]++)(?'
                                .'|/(?'
                                    .'|orders(*:382)'
                                    .'|update_roles(*:402)'
                                    .'|delete(*:416)'
                                .')'
                                .'|(*:425)'
                            .')'
                            .'|order/([^/]++)/(?'
                                .'|update_status(*:465)'
                                .'|delete(*:479)'
                            .')'
                        .')'
                    .')'
                .')'
                .'|/_(?'
                    .'|error/(\\d+)(?:\\.([^/]++))?(*:522)'
                    .'|wdt/([^/]++)(*:542)'
                    .'|profiler/(?'
                        .'|font/([^/\\.]++)\\.woff2(*:584)'
                        .'|([^/]++)(?'
                            .'|/(?'
                                .'|search/results(*:621)'
                                .'|router(*:635)'
                                .'|exception(?'
                                    .'|(*:655)'
                                    .'|\\.css(*:668)'
                                .')'
                            .')'
                            .'|(*:678)'
                        .')'
                    .')'
                .')'
                .'|/verify\\-email/([^/]++)(*:712)'
            .')/?$}sDu',
    ],
    [ // $dynamicRoutes
        46 => [[['_route' => 'api_genid', '_controller' => 'api_platform.action.not_exposed', '_api_respond' => 'true'], ['id'], ['GET' => 0, 'HEAD' => 1], null, false, true, null]],
        65 => [[['_route' => 'api_errors', '_controller' => 'api_platform.action.error_page'], ['status'], ['GET' => 0, 'HEAD' => 1], null, false, true, null]],
        98 => [[['_route' => 'api_validation_errors', '_controller' => 'api_platform.action.not_exposed'], ['id'], ['GET' => 0, 'HEAD' => 1], null, false, true, null]],
        134 => [[['_route' => 'api_entrypoint', '_controller' => 'api_platform.action.entrypoint', '_format' => '', '_api_respond' => 'true', 'index' => 'index'], ['index', '_format'], ['GET' => 0, 'HEAD' => 1], null, false, true, null]],
        165 => [[['_route' => 'api_doc', '_controller' => 'api_platform.action.documentation', '_format' => '', '_api_respond' => 'true'], ['_format'], ['GET' => 0, 'HEAD' => 1], null, false, true, null]],
        204 => [[['_route' => 'api_jsonld_context', '_controller' => 'api_platform.jsonld.action.context', '_format' => 'jsonld', '_api_respond' => 'true'], ['shortName', '_format'], ['GET' => 0, 'HEAD' => 1], null, false, true, null]],
        241 => [
            [['_route' => '_api_validation_errors_problem', '_controller' => 'api_platform.symfony.main_controller', '_format' => null, '_stateless' => true, '_api_resource_class' => 'ApiPlatform\\Validator\\Exception\\ValidationException', '_api_operation_name' => '_api_validation_errors_problem'], ['id'], ['GET' => 0], null, false, true, null],
            [['_route' => '_api_validation_errors_hydra', '_controller' => 'api_platform.symfony.main_controller', '_format' => null, '_stateless' => true, '_api_resource_class' => 'ApiPlatform\\Validator\\Exception\\ValidationException', '_api_operation_name' => '_api_validation_errors_hydra'], ['id'], ['GET' => 0], null, false, true, null],
            [['_route' => '_api_validation_errors_jsonapi', '_controller' => 'api_platform.symfony.main_controller', '_format' => null, '_stateless' => true, '_api_resource_class' => 'ApiPlatform\\Validator\\Exception\\ValidationException', '_api_operation_name' => '_api_validation_errors_jsonapi'], ['id'], ['GET' => 0], null, false, true, null],
        ],
        265 => [[['_route' => 'api_get_images', '_controller' => 'App\\Controller\\ImageController::serveImage'], ['filename'], ['GET' => 0], null, false, true, null]],
        287 => [[['_route' => 'api_get_meals_by_category', '_controller' => 'App\\Controller\\MealController::getMealsByCategory'], ['category'], ['GET' => 0], null, false, true, null]],
        333 => [[['_route' => 'api_update_meal_plan', '_controller' => 'App\\Controller\\MealPlanController::updateMealPlan'], ['id'], ['PUT' => 0], null, false, false, null]],
        347 => [[['_route' => 'api_delete_meal_plan', '_controller' => 'App\\Controller\\MealPlanController::deleteMealPlan'], ['id'], ['DELETE' => 0], null, false, false, null]],
        382 => [[['_route' => 'api_user_orders', '_controller' => 'App\\Controller\\OrderController::getOrdersByUserId'], ['id'], ['GET' => 0], null, false, false, null]],
        402 => [[['_route' => 'api_update_user_roles', '_controller' => 'App\\Controller\\UserController::updateUserRoles'], ['id'], ['PATCH' => 0], null, false, false, null]],
        416 => [[['_route' => 'api_delete_user', '_controller' => 'App\\Controller\\UserController::delete'], ['id'], ['DELETE' => 0], null, false, false, null]],
        425 => [
            [['_route' => 'api_user_byId', '_controller' => 'App\\Controller\\UserController::getUserById'], ['id'], ['GET' => 0], null, false, true, null],
            [['_route' => 'api_patch_details_byId', '_controller' => 'App\\Controller\\UserController::updateDetailsById'], ['id'], ['PUT' => 0], null, false, true, null],
        ],
        465 => [[['_route' => 'api_update_order_status', '_controller' => 'App\\Controller\\OrderController::updateOrderStatus'], ['id'], ['PATCH' => 0], null, false, false, null]],
        479 => [[['_route' => 'api_delete_order', '_controller' => 'App\\Controller\\OrderController::deleteOrder'], ['id'], ['DELETE' => 0], null, false, false, null]],
        522 => [[['_route' => '_preview_error', '_controller' => 'error_controller::preview', '_format' => 'html'], ['code', '_format'], null, null, false, true, null]],
        542 => [[['_route' => '_wdt', '_controller' => 'web_profiler.controller.profiler::toolbarAction'], ['token'], null, null, false, true, null]],
        584 => [[['_route' => '_profiler_font', '_controller' => 'web_profiler.controller.profiler::fontAction'], ['fontName'], null, null, false, false, null]],
        621 => [[['_route' => '_profiler_search_results', '_controller' => 'web_profiler.controller.profiler::searchResultsAction'], ['token'], null, null, false, false, null]],
        635 => [[['_route' => '_profiler_router', '_controller' => 'web_profiler.controller.router::panelAction'], ['token'], null, null, false, false, null]],
        655 => [[['_route' => '_profiler_exception', '_controller' => 'web_profiler.controller.exception_panel::body'], ['token'], null, null, false, false, null]],
        668 => [[['_route' => '_profiler_exception_css', '_controller' => 'web_profiler.controller.exception_panel::stylesheet'], ['token'], null, null, false, false, null]],
        678 => [[['_route' => '_profiler', '_controller' => 'web_profiler.controller.profiler::panelAction'], ['token'], null, null, false, true, null]],
        712 => [
            [['_route' => 'verify_email', '_controller' => 'App\\Controller\\RegisterController::verifyEmail'], ['token'], null, null, false, true, null],
            [null, null, null, null, false, false, 0],
        ],
    ],
    null, // $checkCondition
];
