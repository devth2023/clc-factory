import os
import uuid
import yaml
import time
from typing import Dict, Any
from flask import Flask, request, jsonify
from pydantic import ValidationError

from clc.services import (
    BitMaskEngine,
    CoordinateResolver,
    CallerDetector,
    ProjectionRenderer,
)
from clc.models import TunnelRequest, TunnelResponse, ErrorResponse
from clc.exceptions import CoordinateResolutionException, InvalidBitmaskException


def create_app(config_path: str = "config.yaml") -> Flask:
    app = Flask(__name__)

    registry_path = os.getenv("REGISTRY_PATH", "../master_registry.yaml")
    with open(registry_path, "r", encoding="utf-8") as f:
        registry_data = yaml.safe_load(f)

    projection_data = {
        coord: registry_data.get("projections", {}).get(coord, {})
        for coord in registry_data.get("layer1_human_map", {}).keys()
    }

    resolver = CoordinateResolver(registry_data)
    detector = CallerDetector()
    renderer = ProjectionRenderer(projection_data)
    engine = BitMaskEngine()

    @app.route("/api/sync", methods=["POST"])
    def sync():
        start_time = time.time()
        request_id = str(uuid.uuid4())

        try:
            payload = request.get_json()
            tunnel_request = TunnelRequest(**payload or {})

            user_agent = request.headers.get("User-Agent", "")
            auth_token = request.headers.get("Authorization", "")

            caller_mask = detector.detect(user_agent, auth_token)
            coordinate_data = resolver.resolve(tunnel_request.target)
            projection = renderer.render(
                tunnel_request.target, coordinate_data, caller_mask
            )

            execution_time = int((time.time() - start_time) * 1000)

            return jsonify(
                {
                    "status": 200,
                    "request_id": request_id,
                    "data": projection,
                }
            ), 200

        except ValidationError as e:
            return jsonify(
                {
                    "status": 400,
                    "request_id": request_id,
                    "error": "Invalid request format",
                }
            ), 400

        except CoordinateResolutionException:
            return jsonify(
                {
                    "status": 404,
                    "request_id": request_id,
                    "data": None,
                }
            ), 404

        except Exception as e:
            if os.getenv("APP_DEBUG") == "true":
                app.logger.error(f"Tunnel error: {str(e)}")

            return jsonify(
                {
                    "status": 500,
                    "request_id": request_id,
                    "data": None,
                }
            ), 500

    @app.route("/", methods=["GET"])
    def index():
        return """
        <!DOCTYPE html>
        <html>
        <head><title>CLC Factory</title></head>
        <body>
            <h1>Coordinate Logic Core - Python</h1>
            <div id="result"></div>
            <script>
                fetch('/api/sync', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({target: 'COORD_X101', payload: {}})
                })
                .then(r => r.json())
                .then(d => document.getElementById('result').innerHTML = 
                    '<pre>' + JSON.stringify(d, null, 2) + '</pre>');
            </script>
        </body>
        </html>
        """, 200

    @app.errorhandler(404)
    def not_found(error):
        return jsonify({"status": 404, "error": "Not found"}), 404

    @app.errorhandler(500)
    def server_error(error):
        return jsonify({"status": 500, "error": "Internal server error"}), 500

    return app


if __name__ == "__main__":
    app = create_app()
    app.run(debug=os.getenv("APP_DEBUG", "false").lower() == "true")
