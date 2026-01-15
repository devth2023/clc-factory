from flask import Flask, request, jsonify
from entry import handle_request

app = Flask(__name__)

@app.route('/resolve', methods=['POST'])
def resolve_endpoint():
    try:
        payload = request.get_json()
        user_agent = request.headers.get('User-Agent', '')
        auth_token = request.headers.get('Authorization', '')
        
        response = handle_request(payload, user_agent, auth_token)
        return jsonify(response), response.get('status', 200)
    
    except Exception as e:
        return jsonify({'status': 500, 'data': None, 'mask': '0x0000'}), 500

@app.route('/', methods=['GET'])
def root():
    return '''<!DOCTYPE html>
<html>
<head><title>CLC System</title></head>
<body>
<h1>Coordinate Logic Core</h1>
<div id="result"></div>
<script>
fetch('/resolve', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({target: 'COORD_X101', payload: {}})
})
.then(r => r.json())
.then(d => document.getElementById('result').textContent = JSON.stringify(d, null, 2));
</script>
</body>
</html>'''

if __name__ == '__main__':
    app.run(debug=False, port=5000)
