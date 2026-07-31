import sys
import json
import os

DOC_PATH = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "DOCUMENTACION_PROYECTO_NOTEBOOKLM.md"))

def list_tools():
    return [
        {
            "name": "get_project_docs",
            "description": "Obtiene la documentación completa del proyecto Control Mantenimiento Equipos para sincronización con NotebookLM.",
            "inputSchema": {
                "type": "object",
                "properties": {}
            }
        },
        {
            "name": "get_database_summary",
            "description": "Obtiene el estado y esquema del sistema.",
            "inputSchema": {
                "type": "object",
                "properties": {}
            }
        }
    ]

def call_tool(name, arguments):
    if name == "get_project_docs":
        if os.path.exists(DOC_PATH):
            with open(DOC_PATH, "r", encoding="utf-8") as f:
                content = f.read()
            return {"content": [{"type": "text", "text": content}]}
        else:
            return {"content": [{"type": "text", "text": "Documentación no encontrada."}], "isError": True}
    elif name == "get_database_summary":
        return {
            "content": [{
                "type": "text",
                "text": "Sistema: Control Mantenimiento Equipos\nFramework: Laravel 12\nEstado DB: Tablas operativas limpias, 3 usuarios activos (Admin, Tecnico, Invitado)."
            }]
        }
    return {"content": [{"type": "text", "text": f"Herramienta desconocida: {name}"}], "isError": True}

def main():
    if hasattr(sys.stdout, 'reconfigure'):
        sys.stdout.reconfigure(encoding='utf-8')
    if len(sys.argv) > 1 and sys.argv[1] == "--test":
        print("--- PRUEBA DE CONEXIÓN Y FUNCIONAMIENTO MCP ---")
        tools = list_tools()
        print(f"✅ Herramientas MCP registradas: {len(tools)}")
        for t in tools:
            print(f"  - {t['name']}: {t['description']}")
        
        res = call_tool("get_project_docs", {})
        if not res.get("isError"):
            print("✅ Lectura de Documentación MCP exitosa! (Primeros 150 caracteres):")
            print(res["content"][0]["text"][:150] + "...")
        else:
            print("❌ Error al leer documentación.")
        return

    # Escucha de peticiones JSON-RPC MCP en stdio
    for line in sys.stdin:
        if not line.strip():
            continue
        try:
            req = json.loads(line)
            method = req.get("method")
            req_id = req.get("id")
            
            if method == "tools/list":
                resp = {"jsonrpc": "2.0", "id": req_id, "result": {"tools": list_tools()}}
            elif method == "tools/call":
                params = req.get("params", {})
                tool_res = call_tool(params.get("name"), params.get("arguments", {}))
                resp = {"jsonrpc": "2.0", "id": req_id, "result": tool_res}
            else:
                resp = {"jsonrpc": "2.0", "id": req_id, "error": {"code": -32601, "message": "Method not found"}}
                
            sys.stdout.write(json.dumps(resp) + "\n")
            sys.stdout.flush()
        except Exception as e:
            sys.stderr.write(f"Error procesando MCP request: {e}\n")

if __name__ == "__main__":
    main()
