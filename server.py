import os
from flask import Flask, request, jsonify
from core.pipeline import Pipeline

app = Flask(__name__)
yaml_path = os.path.join(os.path.dirname(__file__), '..', 'master_registry.yaml')
pipeline = Pipeline(yaml_path)

@app.route('/resolve', methods=['POST'])
def resolve():
    try:
        payload = request.get_json()
        ua = request.headers.get('User-Agent', '')
        token = request.headers.get('Authorization', '')
        
        response = pipeline.execute(payload, ua, token)
        return jsonify(response), response.get('status', 200)
    
    except Exception as e:
        return jsonify({'status': 500, 'data': None, 'mask': '0x0000'}), 500

@app.route('/', methods=['GET'])
def index():
    return '''<!DOCTYPE html>
<html>
<head><title>CLC</title></head>
<body>
<h1>Coordinate Logic Core</h1>
<div id="result"></div>
<script>
fetch('/resolve', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({target: 'COORD_X101', payload: {data: 'test'}})
})
.then(r => r.json())
.then(d => document.getElementById('result').innerHTML = '<pre>' + JSON.stringify(d, null, 2) + '</pre>');
</script>
</body>
</html>'''

if __name__ == '__main__':
    app.run(debug=False, port=5000)
