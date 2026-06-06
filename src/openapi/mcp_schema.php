<?php

return [
    'Annotated' => [
        'type' => 'object',
        'properties' => [
            'annotations' => [
                'type' => 'object',
                'properties' => [
                    'audience' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'priority' => ['type' => 'number']
                ]
            ]
        ]
    ],
    'BlobResourceContents' => [
        'type' => 'object',
        'required' => ['uri', 'mimeType', 'blob'],
        'properties' => [
            'uri' => ['type' => 'string'],
            'mimeType' => ['type' => 'string'],
            'blob' => ['type' => 'string']
        ]
    ],
    'CallToolRequest' => [
        'type' => 'object',
        'required' => ['method', 'params'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'required' => ['name'],
                'properties' => [
                    'name' => ['type' => 'string'],
                    'arguments' => ['type' => 'object']
                ]
            ]
        ]
    ],
    'CallToolResult' => [
        'type' => 'object',
        'required' => ['content'],
        'properties' => [
            '_meta' => ['type' => 'object'],
            'content' => ['type' => 'array', 'items' => ['type' => 'object']],
            'isError' => ['type' => 'boolean']
        ]
    ],
    'CancelledNotification' => [
        'type' => 'object',
        'required' => ['method', 'params'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'required' => ['requestId'],
                'properties' => [
                    'requestId' => ['type' => 'string'],
                    'reason' => ['type' => 'string']
                ]
            ]
        ]
    ],
    'ClientCapabilities' => [
        'type' => 'object',
        'properties' => [
            'experimental' => ['type' => 'object'],
            'roots' => [
                'type' => 'object',
                'properties' => [
                    'listChanged' => ['type' => 'boolean']
                ]
            ],
            'sampling' => ['type' => 'object']
        ]
    ],
    'ClientNotification' => ['type' => 'object'],
    'ClientRequest' => ['type' => 'object'],
    'ClientResult' => ['type' => 'object'],
    'CompleteRequest' => [
        'type' => 'object',
        'required' => ['method', 'params'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'required' => ['ref', 'argument'],
                'properties' => [
                    'ref' => ['type' => 'object'],
                    'argument' => [
                        'type' => 'object',
                        'required' => ['name', 'value'],
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'value' => ['type' => 'string']
                        ]
                    ]
                ]
            ]
        ]
    ],
    'CompleteResult' => [
        'type' => 'object',
        'required' => ['completion'],
        'properties' => [
            '_meta' => ['type' => 'object'],
            'completion' => [
                'type' => 'object',
                'required' => ['values', 'hasMore'],
                'properties' => [
                    'values' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'total' => ['type' => 'integer'],
                    'hasMore' => ['type' => 'boolean']
                ]
            ]
        ]
    ],
    'CreateMessageRequest' => [
        'type' => 'object',
        'required' => ['method', 'params'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'required' => ['messages', 'maxTokens'],
                'properties' => [
                    'messages' => ['type' => 'array', 'items' => ['type' => 'object']],
                    'systemPrompt' => ['type' => 'string'],
                    'includeContext' => ['type' => 'string'],
                    'temperature' => ['type' => 'number'],
                    'maxTokens' => ['type' => 'integer'],
                    'stopSequences' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'metadata' => ['type' => 'object'],
                    'modelPreferences' => ['type' => 'object']
                ]
            ]
        ]
    ],
    'CreateMessageResult' => [
        'type' => 'object',
        'required' => ['role', 'content', 'model'],
        'properties' => [
            '_meta' => ['type' => 'object'],
            'role' => ['type' => 'string'],
            'content' => ['type' => 'object'],
            'model' => ['type' => 'string'],
            'stopReason' => ['type' => 'string']
        ]
    ],
    'Cursor' => ['type' => 'string'],
    'EmbeddedResource' => [
        'type' => 'object',
        'required' => ['type', 'resource'],
        'properties' => [
            'type' => ['type' => 'string'],
            'resource' => ['type' => 'object'],
            'annotations' => [
                'type' => 'object',
                'properties' => [
                    'audience' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'priority' => ['type' => 'number']
                ]
            ]
        ]
    ],
    'EmptyResult' => ['type' => 'object'],
    'GetPromptRequest' => [
        'type' => 'object',
        'required' => ['method', 'params'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'required' => ['name'],
                'properties' => [
                    'name' => ['type' => 'string'],
                    'arguments' => ['type' => 'object']
                ]
            ]
        ]
    ],
    'GetPromptResult' => [
        'type' => 'object',
        'required' => ['messages'],
        'properties' => [
            '_meta' => ['type' => 'object'],
            'description' => ['type' => 'string'],
            'messages' => ['type' => 'array', 'items' => ['type' => 'object']]
        ]
    ],
    'ImageContent' => [
        'type' => 'object',
        'required' => ['type', 'data', 'mimeType'],
        'properties' => [
            'type' => ['type' => 'string'],
            'data' => ['type' => 'string'],
            'mimeType' => ['type' => 'string'],
            'annotations' => [
                'type' => 'object',
                'properties' => [
                    'audience' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'priority' => ['type' => 'number']
                ]
            ]
        ]
    ],
    'Implementation' => [
        'type' => 'object',
        'required' => ['name', 'version'],
        'properties' => [
            'name' => ['type' => 'string'],
            'version' => ['type' => 'string']
        ]
    ],
    'InitializeRequest' => [
        'type' => 'object',
        'required' => ['method', 'params'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'required' => ['protocolVersion', 'capabilities', 'clientInfo'],
                'properties' => [
                    'protocolVersion' => ['type' => 'string'],
                    'capabilities' => ['type' => 'object'],
                    'clientInfo' => ['type' => 'object']
                ]
            ]
        ]
    ],
    'InitializeResult' => [
        'type' => 'object',
        'required' => ['protocolVersion', 'capabilities', 'serverInfo'],
        'properties' => [
            '_meta' => ['type' => 'object'],
            'protocolVersion' => ['type' => 'string'],
            'capabilities' => ['type' => 'object'],
            'serverInfo' => ['type' => 'object'],
            'instructions' => ['type' => 'string']
        ]
    ],
    'InitializedNotification' => [
        'type' => 'object',
        'required' => ['method'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'properties' => [
                    '_meta' => ['type' => 'object']
                ]
            ]
        ]
    ],
    'JSONRPCError' => [
        'type' => 'object',
        'required' => ['jsonrpc', 'id', 'error'],
        'properties' => [
            'jsonrpc' => ['type' => 'string'],
            'id' => ['type' => 'string'],
            'error' => [
                'type' => 'object',
                'required' => ['code', 'message'],
                'properties' => [
                    'code' => ['type' => 'integer'],
                    'message' => ['type' => 'string'],
                    'data' => ['type' => 'object']
                ]
            ]
        ]
    ],
    'JSONRPCMessage' => ['type' => 'object'],
    'JSONRPCNotification' => [
        'type' => 'object',
        'required' => ['jsonrpc', 'method'],
        'properties' => [
            'jsonrpc' => ['type' => 'string'],
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'properties' => [
                    '_meta' => ['type' => 'object']
                ]
            ]
        ]
    ],
    'JSONRPCRequest' => [
        'type' => 'object',
        'required' => ['jsonrpc', 'id', 'method'],
        'properties' => [
            'jsonrpc' => ['type' => 'string'],
            'id' => ['type' => 'string'],
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'properties' => [
                    '_meta' => [
                        'type' => 'object',
                        'properties' => [
                            'progressToken' => ['type' => 'string']
                        ]
                    ]
                ]
            ]
        ]
    ],
    'JSONRPCResponse' => [
        'type' => 'object',
        'required' => ['jsonrpc', 'id', 'result'],
        'properties' => [
            'jsonrpc' => ['type' => 'string'],
            'id' => ['type' => 'string'],
            'result' => ['type' => 'object']
        ]
    ],
    'ListPromptsRequest' => [
        'type' => 'object',
        'required' => ['method'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'properties' => [
                    'cursor' => ['type' => 'string']
                ]
            ]
        ]
    ],
    'ListPromptsResult' => [
        'type' => 'object',
        'required' => ['prompts'],
        'properties' => [
            '_meta' => ['type' => 'object'],
            'prompts' => ['type' => 'array', 'items' => ['type' => 'object']],
            'nextCursor' => ['type' => 'string']
        ]
    ],
    'ListResourceTemplatesRequest' => [
        'type' => 'object',
        'required' => ['method'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'properties' => [
                    'cursor' => ['type' => 'string']
                ]
            ]
        ]
    ],
    'ListResourceTemplatesResult' => [
        'type' => 'object',
        'required' => ['resourceTemplates'],
        'properties' => [
            '_meta' => ['type' => 'object'],
            'resourceTemplates' => ['type' => 'array', 'items' => ['type' => 'object']],
            'nextCursor' => ['type' => 'string']
        ]
    ],
    'ListResourcesRequest' => [
        'type' => 'object',
        'required' => ['method'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'properties' => [
                    'cursor' => ['type' => 'string']
                ]
            ]
        ]
    ],
    'ListResourcesResult' => [
        'type' => 'object',
        'required' => ['resources'],
        'properties' => [
            '_meta' => ['type' => 'object'],
            'resources' => ['type' => 'array', 'items' => ['type' => 'object']],
            'nextCursor' => ['type' => 'string']
        ]
    ],
    'ListRootsRequest' => [
        'type' => 'object',
        'required' => ['method'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'properties' => [
                    '_meta' => [
                        'type' => 'object',
                        'properties' => [
                            'progressToken' => ['type' => 'string']
                        ]
                    ]
                ]
            ]
        ]
    ],
    'ListRootsResult' => [
        'type' => 'object',
        'required' => ['roots'],
        'properties' => [
            '_meta' => ['type' => 'object'],
            'roots' => ['type' => 'array', 'items' => ['type' => 'object']]
        ]
    ],
    'ListToolsRequest' => [
        'type' => 'object',
        'required' => ['method'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'properties' => [
                    'cursor' => ['type' => 'string']
                ]
            ]
        ]
    ],
    'ListToolsResult' => [
        'type' => 'object',
        'required' => ['tools'],
        'properties' => [
            '_meta' => ['type' => 'object'],
            'tools' => ['type' => 'array', 'items' => ['type' => 'object']],
            'nextCursor' => ['type' => 'string']
        ]
    ],
    'LoggingLevel' => ['type' => 'string'],
    'LoggingMessageNotification' => [
        'type' => 'object',
        'required' => ['method', 'params'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'required' => ['level', 'data'],
                'properties' => [
                    'level' => ['type' => 'string'],
                    'logger' => ['type' => 'string'],
                    'data' => ['type' => 'object']
                ]
            ]
        ]
    ],
    'ModelHint' => [
        'type' => 'object',
        'properties' => [
            'name' => ['type' => 'string']
        ]
    ],
    'ModelPreferences' => [
        'type' => 'object',
        'properties' => [
            'hints' => ['type' => 'array', 'items' => ['type' => 'object']],
            'costPriority' => ['type' => 'number'],
            'speedPriority' => ['type' => 'number'],
            'intelligencePriority' => ['type' => 'number']
        ]
    ],
    'Notification' => [
        'type' => 'object',
        'required' => ['method'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'properties' => [
                    '_meta' => ['type' => 'object']
                ]
            ]
        ]
    ],
    'PaginatedRequest' => [
        'type' => 'object',
        'required' => ['method'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'properties' => [
                    'cursor' => ['type' => 'string']
                ]
            ]
        ]
    ],
    'PaginatedResult' => [
        'type' => 'object',
        'properties' => [
            '_meta' => ['type' => 'object'],
            'nextCursor' => ['type' => 'string']
        ]
    ],
    'PingRequest' => [
        'type' => 'object',
        'required' => ['method'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'properties' => [
                    '_meta' => [
                        'type' => 'object',
                        'properties' => [
                            'progressToken' => ['type' => 'string']
                        ]
                    ]
                ]
            ]
        ]
    ],
    'ProgressNotification' => [
        'type' => 'object',
        'required' => ['method', 'params'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'required' => ['progressToken', 'progress'],
                'properties' => [
                    'progressToken' => ['type' => 'string'],
                    'progress' => ['type' => 'number'],
                    'total' => ['type' => 'number']
                ]
            ]
        ]
    ],
    'ProgressToken' => ['type' => 'string'],
    'Prompt' => [
        'type' => 'object',
        'required' => ['name'],
        'properties' => [
            'name' => ['type' => 'string'],
            'description' => ['type' => 'string'],
            'arguments' => ['type' => 'array', 'items' => ['type' => 'object']]
        ]
    ],
    'PromptArgument' => [
        'type' => 'object',
        'required' => ['name'],
        'properties' => [
            'name' => ['type' => 'string'],
            'description' => ['type' => 'string'],
            'required' => ['type' => 'boolean']
        ]
    ],
    'PromptListChangedNotification' => [
        'type' => 'object',
        'required' => ['method'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'properties' => [
                    '_meta' => ['type' => 'object']
                ]
            ]
        ]
    ],
    'PromptMessage' => [
        'type' => 'object',
        'required' => ['role', 'content'],
        'properties' => [
            'role' => ['type' => 'string'],
            'content' => ['type' => 'object']
        ]
    ],
    'PromptReference' => [
        'type' => 'object',
        'required' => ['type', 'name'],
        'properties' => [
            'type' => ['type' => 'string'],
            'name' => ['type' => 'string']
        ]
    ],
    'ReadResourceRequest' => [
        'type' => 'object',
        'required' => ['method', 'params'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'required' => ['uri'],
                'properties' => [
                    'uri' => ['type' => 'string']
                ]
            ]
        ]
    ],
    'ReadResourceResult' => [
        'type' => 'object',
        'required' => ['contents'],
        'properties' => [
            '_meta' => ['type' => 'object'],
            'contents' => ['type' => 'array', 'items' => ['type' => 'object']]
        ]
    ],
    'Request' => [
        'type' => 'object',
        'required' => ['method'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'properties' => [
                    '_meta' => [
                        'type' => 'object',
                        'properties' => [
                            'progressToken' => ['type' => 'string']
                        ]
                    ]
                ]
            ]
        ]
    ],
    'RequestId' => ['type' => 'string'],
    'Resource' => [
        'type' => 'object',
        'required' => ['uri', 'name'],
        'properties' => [
            'uri' => ['type' => 'string'],
            'name' => ['type' => 'string'],
            'description' => ['type' => 'string'],
            'mimeType' => ['type' => 'string'],
            'size' => ['type' => 'integer'],
            'annotations' => [
                'type' => 'object',
                'properties' => [
                    'audience' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'priority' => ['type' => 'number']
                ]
            ]
        ]
    ],
    'ResourceContents' => [
        'type' => 'object',
        'required' => ['uri'],
        'properties' => [
            'uri' => ['type' => 'string'],
            'mimeType' => ['type' => 'string']
        ]
    ],
    'ResourceListChangedNotification' => [
        'type' => 'object',
        'required' => ['method'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'properties' => [
                    '_meta' => ['type' => 'object']
                ]
            ]
        ]
    ],
    'ResourceReference' => [
        'type' => 'object',
        'required' => ['type', 'uri'],
        'properties' => [
            'type' => ['type' => 'string'],
            'uri' => ['type' => 'string']
        ]
    ],
    'ResourceTemplate' => [
        'type' => 'object',
        'required' => ['uriTemplate', 'name'],
        'properties' => [
            'uriTemplate' => ['type' => 'string'],
            'name' => ['type' => 'string'],
            'description' => ['type' => 'string'],
            'mimeType' => ['type' => 'string'],
            'annotations' => [
                'type' => 'object',
                'properties' => [
                    'audience' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'priority' => ['type' => 'number']
                ]
            ]
        ]
    ],
    'ResourceUpdatedNotification' => [
        'type' => 'object',
        'required' => ['method', 'params'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'required' => ['uri'],
                'properties' => [
                    'uri' => ['type' => 'string']
                ]
            ]
        ]
    ],
    'Result' => [
        'type' => 'object',
        'properties' => [
            '_meta' => ['type' => 'object']
        ]
    ],
    'Role' => ['type' => 'string'],
    'Root' => [
        'type' => 'object',
        'required' => ['uri'],
        'properties' => [
            'uri' => ['type' => 'string'],
            'name' => ['type' => 'string']
        ]
    ],
    'RootsListChangedNotification' => [
        'type' => 'object',
        'required' => ['method'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'properties' => [
                    '_meta' => ['type' => 'object']
                ]
            ]
        ]
    ],
    'SamplingMessage' => [
        'type' => 'object',
        'required' => ['role', 'content'],
        'properties' => [
            'role' => ['type' => 'string'],
            'content' => ['type' => 'object']
        ]
    ],
    'ServerCapabilities' => [
        'type' => 'object',
        'properties' => [
            'experimental' => ['type' => 'object'],
            'logging' => ['type' => 'object'],
            'prompts' => [
                'type' => 'object',
                'properties' => [
                    'listChanged' => ['type' => 'boolean']
                ]
            ],
            'resources' => [
                'type' => 'object',
                'properties' => [
                    'listChanged' => ['type' => 'boolean'],
                    'subscribe' => ['type' => 'boolean']
                ]
            ],
            'tools' => [
                'type' => 'object',
                'properties' => [
                    'listChanged' => ['type' => 'boolean']
                ]
            ]
        ]
    ],
    'ServerNotification' => ['type' => 'object'],
    'ServerRequest' => ['type' => 'object'],
    'ServerResult' => ['type' => 'object'],
    'SetLevelRequest' => [
        'type' => 'object',
        'required' => ['method', 'params'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'required' => ['level'],
                'properties' => [
                    'level' => ['type' => 'string']
                ]
            ]
        ]
    ],
    'SubscribeRequest' => [
        'type' => 'object',
        'required' => ['method', 'params'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'required' => ['uri'],
                'properties' => [
                    'uri' => ['type' => 'string']
                ]
            ]
        ]
    ],
    'TextContent' => [
        'type' => 'object',
        'required' => ['type', 'text'],
        'properties' => [
            'type' => ['type' => 'string'],
            'text' => ['type' => 'string'],
            'annotations' => [
                'type' => 'object',
                'properties' => [
                    'audience' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'priority' => ['type' => 'number']
                ]
            ]
        ]
    ],
    'TextResourceContents' => [
        'type' => 'object',
        'required' => ['uri', 'text'],
        'properties' => [
            'uri' => ['type' => 'string'],
            'mimeType' => ['type' => 'string'],
            'text' => ['type' => 'string']
        ]
    ],
    'Tool' => [
        'type' => 'object',
        'required' => ['name', 'inputSchema'],
        'properties' => [
            'name' => ['type' => 'string'],
            'description' => ['type' => 'string'],
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'type' => ['type' => 'string'],
                    'properties' => ['type' => 'object'],
                    'required' => ['type' => 'array', 'items' => ['type' => 'string']]
                ]
            ]
        ]
    ],
    'ToolListChangedNotification' => [
        'type' => 'object',
        'required' => ['method'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'properties' => [
                    '_meta' => ['type' => 'object']
                ]
            ]
        ]
    ],
    'UnsubscribeRequest' => [
        'type' => 'object',
        'required' => ['method', 'params'],
        'properties' => [
            'method' => ['type' => 'string'],
            'params' => [
                'type' => 'object',
                'required' => ['uri'],
                'properties' => [
                    'uri' => ['type' => 'string']
                ]
            ]
        ]
    ]
];
