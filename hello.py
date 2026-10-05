import json
import os
import urllib.request

API_KEY = os.environ.get("THURUK_API_KEY", "f965cf88b8f475852d3af6d9b35778f29f4f46622b25ef4f49ee827710406ced_1")
URL = "https://monitoring-dr.options-it.com/thruk/r/hosts"

request = urllib.request.Request(URL, headers={"X-Thruk-Auth-Key": API_KEY})
with urllib.request.urlopen(request) as response:
    data = json.load(response)

hosts = data.get("data", data) if isinstance(data, dict) else data
for host in hosts:
    print(host.get("name", host.get("host_name")))
