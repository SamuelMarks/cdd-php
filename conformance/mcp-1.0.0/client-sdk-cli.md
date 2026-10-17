# Model Context Protocol (MCP) CLI Conformance Table

This table tracks the completeness of language CLI integration with the Model Context Protocol (MCP). It is divided into three sections:
1. **Architectural Integration Layers**: Tracks the exposure of MCP across the CLI, SDK, and Server boundaries.
2. **Semantic & Conceptual Features**: Tracks protocol mechanics, transports, and behavioral requirements.
3. **Schema & Object Conformance**: An exhaustive property-by-property map derived directly from the official MCP JSON Schema (2024-11-05).

### Legend & Tracking Guide
*   **To**: Language -> MCP (Generating MCP Server payloads and handling requests from strongly typed code)
*   **From**: MCP -> Language (Generating MCP Client code, parsing responses, and invoking remote methods)
*   **Presence `[To, From]`**: The object/feature is successfully parsed, validated, utilized, or generated.
*   **Absence `[To, From]`**: The object/feature is currently unsupported, dropped, or falls back to generic/`any` types.
*   **Skipped `[To, From]`**: Intentionally ignored because it is irrelevant or unsupported by the Client architecture.
*   **Checkboxes**: Mark `[x]` as conformance is achieved.

## 1. Architectural Integration Layers

This section tracks how the Model Context Protocol is exposed across both the **Generated Artifacts** (the output SDKs/APIs) and the **Generator Tooling** itself (the bidirectional `cdd` compiler/engine).

### 1A. Target/Generated Artifacts
Implementing MCP across the generated output ensures maximum flexibility for the end-user's AI architectures:

*   **CLI Integration (Local Desktop via `stdio`)**: Enables local AI assistants (Claude Desktop, Cursor, Windsurf) to spawn the generated CLI as a subprocess and natively interact with the API locally.
*   **SDK Integration (Programmatic / In-Memory)**: Provides native adapters (e.g., `client.mcp.get_tools()`) so developers can seamlessly attach the generated SDK to frameworks like LangChain, LlamaIndex, or raw LLM clients without network overhead.
*   **Server Integration (Remote AI Gateway via `sse`)**: Generates an AI Gateway endpoint (e.g., `/mcp/sse`), allowing remote, multi-tenant AI agents and web clients to securely consume the API as LLM tools over HTTP.

| Generated Boundary | Presence `[To, From]` | Absence `[To, From]` | Skipped `[To, From]` | Notes / Implementation Strategy |
| :--- | :---: | :---: | :---: | :--- |
| **CLI Integration (Local Desktop)** | | | | |
| CLI `mcp` Subcommand | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` | Generates a command (e.g., app mcp) to start the server |
| `stdio` Transport Bindings | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` | Wires stdin/stdout to the generated CLI logic |
| **SDK Integration (Programmatic)** | | | | |
| Native MCP Tool Adapter | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` | E.g., client.mcp.get_tools() mapping SDK methods |
| Native MCP Resource Adapter | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` | Exposes internal state/docs as MCP resources |
| LLM Execution Router | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` | Native execution via client.mcp.execute_tool(name, args) |
| **Server Integration (Remote / SSE)** | | | | |
| SSE Endpoint Generation | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` | Wires MCP endpoints (e.g. /mcp/sse, /mcp/message) |
| HTTP Request/Auth Bridging | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` | Passes standard API auth into the MCP context |
| Dynamic API-to-Tool Proxy | `[x]` , `[ ]` | `[ ]` , `[ ]` | `[ ]` , `[x]` | Resolves incoming tool calls to backend route handlers |

### 1B. Generator/Tooling Artifacts (Meta-MCP)
Exposing the `cdd` bidirectional code generator itself to MCP allows AI models to natively orchestrate code generation, schema manipulation, and code-to-schema extraction.

*   **Generator CLI via `stdio`**: Allows local IDEs or AI agents to directly instruct the generator to scaffold, diff, or compile code across languages (e.g., Tool: `cdd_generate(lang="python")`).
*   **Generator SDK / Core**: Exposes the AST and schema parsing engine natively to MCP, allowing AI tools to dynamically query API specs, understand types, and invoke generator internals in memory.

| Generator Boundary | Presence `[To, From]` | Absence `[To, From]` | Skipped `[To, From]` | Notes / Implementation Strategy |
| :--- | :---: | :---: | :---: | :--- |
| **Generator CLI (`stdio`)** | | | | |
| Code Scaffold / Generate Tools | `[x]` , `[ ]` | `[ ]` , `[ ]` | `[ ]` , `[x]` | AI can invoke standard generator CLI commands via MCP |
| Schema Inspection Tools | `[x]` , `[ ]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` | AI can query loaded OpenAPI/AsyncAPI schemas |
| Bidirectional Sync Tools | `[x]` , `[ ]` | `[ ]` , `[ ]` | `[ ]` , `[x]` | AI can trigger code-to-schema extraction natively |
| **Generator SDK / Core** | | | | |
| AST / Type Query Resources | `[x]` , `[ ]` | `[ ]` , `[ ]` | `[ ]` , `[x]` | AI can read internal AST structures as MCP resources |
| In-Memory Generation Router | `[x]` , `[ ]` | `[ ]` , `[ ]` | `[ ]` , `[x]` | Native bindings to run the generator core directly via MCP |

## 2. Semantic & Conceptual Features

| MCP Feature / Behavior | Presence `[To, From]` | Absence `[To, From]` | Skipped `[To, From]` | Notes / Implementation Strategy |
| :--- | :---: | :---: | :---: | :--- |
| **Transports** | | | | |
| Standard I/O (stdio) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` | stdin/stdout message passing |
| Server-Sent Events (sse) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` | HTTP POST + SSE streams |
| Custom Transports | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Pluggable transport interface |
| **JSON-RPC 2.0 Mechanics** | | | | |
| Message Parsing & Serialization | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| Request ID Mapping/Resolution | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` | Resolving async responses to requests |
| Error Code Mapping (Standard) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` | Codes like -32600, -32603 |
| Notification Handling | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` | Processing fire-and-forget messages |
| **Connection Lifecycle** | | | | |
| initialize Handshake Sequence | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` | Capability negotiation & version matching |
| initialized Acknowledgment | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` | Sent by client after successful initialization |
| Graceful Disconnect / Close | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| Liveness (ping) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` | Periodic connection checks |
| Request Cancellation (cancelled) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` | Thread/Task abortion mechanics |
| **Behavioral & Security** | | | | |
| Pagination Cursor Management | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Handling nextCursor fetch loops |
| Progress Tracking (progress) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Emitting/handling progress events |
| Human-in-the-loop (Sampling) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Prompting user before LLM generation |
| Human-in-the-loop (Tools) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Security approvals/denials for tool calls |
| Root Boundary Enforcement | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Preventing traversal outside allowed directories |
| URI Protocol Handling | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Resolving custom URI schemes |

## 3. Schema & Object Conformance

| Schema Definition / Property | Presence `[To, From]` | Absence `[To, From]` | Skipped `[To, From]` | Notes |
| :--- | :---: | :---: | :---: | :--- |
| **Annotated** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| Annotated (`annotations`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| Annotated (`annotations`) (`audience`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| Annotated (`annotations`) (`priority`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **BlobResourceContents** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| BlobResourceContents (`blob`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| BlobResourceContents (`mimeType`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| BlobResourceContents (`uri`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **CallToolRequest** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| CallToolRequest (`method`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| CallToolRequest (`params`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| CallToolRequest (`params`) (`arguments`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| CallToolRequest (`params`) (`name`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| **CallToolResult** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| CallToolResult (`_meta`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| CallToolResult (`content`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| CallToolResult (`isError`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| **CancelledNotification** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| CancelledNotification (`method`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| CancelledNotification (`params`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| CancelledNotification (`params`) (`reason`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| CancelledNotification (`params`) (`requestId`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| **ClientCapabilities** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ClientCapabilities (`experimental`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ClientCapabilities (`roots`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ClientCapabilities (`roots`) (`listChanged`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ClientCapabilities (`sampling`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **ClientNotification** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **ClientRequest** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **ClientResult** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **CompleteRequest** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| CompleteRequest (`method`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| CompleteRequest (`params`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| CompleteRequest (`params`) (`argument`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| CompleteRequest (`params`) (`argument`) (`name`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| CompleteRequest (`params`) (`argument`) (`value`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| CompleteRequest (`params`) (`ref`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| **CompleteResult** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| CompleteResult (`_meta`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| CompleteResult (`completion`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| CompleteResult (`completion`) (`hasMore`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| CompleteResult (`completion`) (`total`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| CompleteResult (`completion`) (`values`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| **CreateMessageRequest** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| CreateMessageRequest (`method`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| CreateMessageRequest (`params`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| CreateMessageRequest (`params`) (`includeContext`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| CreateMessageRequest (`params`) (`maxTokens`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| CreateMessageRequest (`params`) (`messages`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| CreateMessageRequest (`params`) (`metadata`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| CreateMessageRequest (`params`) (`modelPreferences`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| CreateMessageRequest (`params`) (`stopSequences`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| CreateMessageRequest (`params`) (`systemPrompt`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| CreateMessageRequest (`params`) (`temperature`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **CreateMessageResult** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| CreateMessageResult (`_meta`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| CreateMessageResult (`content`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| CreateMessageResult (`model`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| CreateMessageResult (`role`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| CreateMessageResult (`stopReason`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **Cursor** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **EmbeddedResource** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| EmbeddedResource (`annotations`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| EmbeddedResource (`annotations`) (`audience`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| EmbeddedResource (`annotations`) (`priority`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| EmbeddedResource (`resource`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| EmbeddedResource (`type`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **EmptyResult** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **GetPromptRequest** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| GetPromptRequest (`method`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| GetPromptRequest (`params`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| GetPromptRequest (`params`) (`arguments`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| GetPromptRequest (`params`) (`name`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| **GetPromptResult** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| GetPromptResult (`_meta`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| GetPromptResult (`description`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| GetPromptResult (`messages`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **ImageContent** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ImageContent (`annotations`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ImageContent (`annotations`) (`audience`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ImageContent (`annotations`) (`priority`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ImageContent (`data`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ImageContent (`mimeType`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ImageContent (`type`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **Implementation** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| Implementation (`name`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| Implementation (`version`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| **InitializeRequest** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| InitializeRequest (`method`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| InitializeRequest (`params`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| InitializeRequest (`params`) (`capabilities`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| InitializeRequest (`params`) (`clientInfo`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| InitializeRequest (`params`) (`protocolVersion`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| **InitializeResult** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| InitializeResult (`_meta`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| InitializeResult (`capabilities`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| InitializeResult (`instructions`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| InitializeResult (`protocolVersion`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| InitializeResult (`serverInfo`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| **InitializedNotification** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| InitializedNotification (`method`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| InitializedNotification (`params`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| InitializedNotification (`params`) (`_meta`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **JSONRPCError** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| JSONRPCError (`error`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| JSONRPCError (`error`) (`code`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| JSONRPCError (`error`) (`data`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| JSONRPCError (`error`) (`message`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| JSONRPCError (`id`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| JSONRPCError (`jsonrpc`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| **JSONRPCMessage** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **JSONRPCNotification** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| JSONRPCNotification (`jsonrpc`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| JSONRPCNotification (`method`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| JSONRPCNotification (`params`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| JSONRPCNotification (`params`) (`_meta`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **JSONRPCRequest** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| JSONRPCRequest (`id`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| JSONRPCRequest (`jsonrpc`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| JSONRPCRequest (`method`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| JSONRPCRequest (`params`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| JSONRPCRequest (`params`) (`_meta`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| JSONRPCRequest (`params`) (`_meta`) (`progressToken`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **JSONRPCResponse** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| JSONRPCResponse (`id`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| JSONRPCResponse (`jsonrpc`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| JSONRPCResponse (`result`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| **ListPromptsRequest** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ListPromptsRequest (`method`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| ListPromptsRequest (`params`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ListPromptsRequest (`params`) (`cursor`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **ListPromptsResult** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ListPromptsResult (`_meta`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ListPromptsResult (`nextCursor`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ListPromptsResult (`prompts`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| **ListResourceTemplatesRequest** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ListResourceTemplatesRequest (`method`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| ListResourceTemplatesRequest (`params`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ListResourceTemplatesRequest (`params`) (`cursor`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **ListResourceTemplatesResult** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ListResourceTemplatesResult (`_meta`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ListResourceTemplatesResult (`nextCursor`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ListResourceTemplatesResult (`resourceTemplates`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| **ListResourcesRequest** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ListResourcesRequest (`method`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| ListResourcesRequest (`params`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ListResourcesRequest (`params`) (`cursor`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **ListResourcesResult** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ListResourcesResult (`_meta`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ListResourcesResult (`nextCursor`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ListResourcesResult (`resources`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| **ListRootsRequest** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ListRootsRequest (`method`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ListRootsRequest (`params`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ListRootsRequest (`params`) (`_meta`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ListRootsRequest (`params`) (`_meta`) (`progressToken`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **ListRootsResult** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ListRootsResult (`_meta`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ListRootsResult (`roots`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **ListToolsRequest** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ListToolsRequest (`method`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| ListToolsRequest (`params`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ListToolsRequest (`params`) (`cursor`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **ListToolsResult** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ListToolsResult (`_meta`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ListToolsResult (`nextCursor`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ListToolsResult (`tools`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| **LoggingLevel** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **LoggingMessageNotification** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| LoggingMessageNotification (`method`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| LoggingMessageNotification (`params`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| LoggingMessageNotification (`params`) (`data`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| LoggingMessageNotification (`params`) (`level`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| LoggingMessageNotification (`params`) (`logger`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **ModelHint** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ModelHint (`name`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **ModelPreferences** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ModelPreferences (`costPriority`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ModelPreferences (`hints`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ModelPreferences (`intelligencePriority`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ModelPreferences (`speedPriority`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **Notification** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| Notification (`method`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| Notification (`params`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| Notification (`params`) (`_meta`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **PaginatedRequest** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| PaginatedRequest (`method`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| PaginatedRequest (`params`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| PaginatedRequest (`params`) (`cursor`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **PaginatedResult** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| PaginatedResult (`_meta`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| PaginatedResult (`nextCursor`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **PingRequest** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| PingRequest (`method`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| PingRequest (`params`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| PingRequest (`params`) (`_meta`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| PingRequest (`params`) (`_meta`) (`progressToken`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **ProgressNotification** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ProgressNotification (`method`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ProgressNotification (`params`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ProgressNotification (`params`) (`progressToken`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ProgressNotification (`params`) (`progress`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ProgressNotification (`params`) (`total`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **ProgressToken** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **Prompt** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| Prompt (`arguments`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| Prompt (`description`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| Prompt (`name`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **PromptArgument** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| PromptArgument (`description`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| PromptArgument (`name`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| PromptArgument (`required`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **PromptListChangedNotification** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| PromptListChangedNotification (`method`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| PromptListChangedNotification (`params`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| PromptListChangedNotification (`params`) (`_meta`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **PromptMessage** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| PromptMessage (`content`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| PromptMessage (`role`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **PromptReference** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| PromptReference (`name`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| PromptReference (`type`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **ReadResourceRequest** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ReadResourceRequest (`method`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| ReadResourceRequest (`params`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| ReadResourceRequest (`params`) (`uri`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| **ReadResourceResult** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ReadResourceResult (`_meta`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ReadResourceResult (`contents`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| **Request** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| Request (`method`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| Request (`params`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| Request (`params`) (`_meta`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| Request (`params`) (`_meta`) (`progressToken`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **RequestId** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **Resource** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| Resource (`annotations`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| Resource (`annotations`) (`audience`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| Resource (`annotations`) (`priority`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| Resource (`description`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| Resource (`mimeType`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| Resource (`name`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| Resource (`size`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| Resource (`uri`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| **ResourceContents** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ResourceContents (`mimeType`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| ResourceContents (`uri`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| **ResourceListChangedNotification** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ResourceListChangedNotification (`method`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ResourceListChangedNotification (`params`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ResourceListChangedNotification (`params`) (`_meta`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **ResourceReference** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ResourceReference (`type`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ResourceReference (`uri`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **ResourceTemplate** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ResourceTemplate (`annotations`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ResourceTemplate (`annotations`) (`audience`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ResourceTemplate (`annotations`) (`priority`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ResourceTemplate (`description`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ResourceTemplate (`mimeType`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ResourceTemplate (`name`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ResourceTemplate (`uriTemplate`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **ResourceUpdatedNotification** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ResourceUpdatedNotification (`method`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ResourceUpdatedNotification (`params`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ResourceUpdatedNotification (`params`) (`uri`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **Result** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| Result (`_meta`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **Role** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **Root** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| Root (`name`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| Root (`uri`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **RootsListChangedNotification** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| RootsListChangedNotification (`method`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| RootsListChangedNotification (`params`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| RootsListChangedNotification (`params`) (`_meta`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **SamplingMessage** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| SamplingMessage (`content`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| SamplingMessage (`role`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **ServerCapabilities** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ServerCapabilities (`experimental`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ServerCapabilities (`logging`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ServerCapabilities (`prompts`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ServerCapabilities (`prompts`) (`listChanged`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ServerCapabilities (`resources`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| ServerCapabilities (`resources`) (`listChanged`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ServerCapabilities (`resources`) (`subscribe`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ServerCapabilities (`tools`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| ServerCapabilities (`tools`) (`listChanged`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **ServerNotification** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **ServerRequest** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **ServerResult** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **SetLevelRequest** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| SetLevelRequest (`method`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| SetLevelRequest (`params`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| SetLevelRequest (`params`) (`level`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| **SubscribeRequest** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| SubscribeRequest (`method`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| SubscribeRequest (`params`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| SubscribeRequest (`params`) (`uri`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **TextContent** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| TextContent (`annotations`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| TextContent (`annotations`) (`audience`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| TextContent (`annotations`) (`priority`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| TextContent (`text`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| TextContent (`type`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| **TextResourceContents** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| TextResourceContents (`mimeType`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| TextResourceContents (`text`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| TextResourceContents (`uri`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| **Tool** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| Tool (`description`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| Tool (`inputSchema`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| Tool (`inputSchema`) (`properties`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| Tool (`inputSchema`) (`required`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| Tool (`inputSchema`) (`type`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| Tool (`name`) | `[x]` , `[x]` | `[ ]` , `[ ]` | `[ ]` , `[ ]` |  |
| **ToolListChangedNotification** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ToolListChangedNotification (`method`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ToolListChangedNotification (`params`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| ToolListChangedNotification (`params`) (`_meta`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| **UnsubscribeRequest** | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| UnsubscribeRequest (`method`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| UnsubscribeRequest (`params`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
| UnsubscribeRequest (`params`) (`uri`) | `[ ]` , `[ ]` | `[ ]` , `[ ]` | `[x]` , `[x]` | Not applicable to this profile |
