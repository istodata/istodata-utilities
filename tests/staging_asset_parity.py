"""Verify generated staging CSS bytes, retaining every original version URL."""
import hashlib, html, json, shlex
from urllib.parse import urlsplit, parse_qsl

HOST='wordpress-218158-6702910.cloudwaysapps.com'
WEB='/home/master/applications/manqbfzxjy/public_html'

def capture(assets, remote):
    urls=[html.unescape(u) for u in assets if urlsplit(html.unescape(u)).hostname==HOST
          and urlsplit(html.unescape(u)).path.startswith('/wp-content/uploads/elementor/')
          and urlsplit(html.unescape(u)).path.endswith('.css')]
    script="""import json,pathlib,hashlib
root=pathlib.Path("""+repr(WEB)+""").resolve()
urls=json.loads("""+repr(json.dumps(urls))+""")
from urllib.parse import urlsplit,unquote
out=[]
for url in urls:
 path=(root/unquote(urlsplit(url).path).lstrip('/')).resolve()
 assert path.is_relative_to(root/'wp-content/uploads/elementor')
 data=path.read_bytes()
 out.append(dict(url=url,path=urlsplit(url).path,bytes=len(data),sha256=hashlib.sha256(data).hexdigest()))
print(json.dumps(out))
"""
    return json.loads(remote('python3 -c '+shlex.quote(script)))

def verify(reference, candidate):
    """Only audited ver-only changes with equal captured bytes are admissible."""
    a=reference['assets']; b=candidate['assets']
    assert len(a)==len(b), 'Asset count differs'
    ac={x['url']:x for x in reference['generated_css']}
    bc={x['url']:x for x in candidate['generated_css']}
    changes=[]
    for u,v in zip(a,b):
        u=html.unescape(u);v=html.unescape(v)
        if u!=v:
            x,y=urlsplit(u),urlsplit(v)
            assert x._replace(query='')==y._replace(query=''), 'Asset path/order differs'
            assert [(k,q) for k,q in parse_qsl(x.query) if k!='ver']==[(k,q) for k,q in parse_qsl(y.query) if k!='ver'], 'Non-version asset query differs'
            assert u in ac and v in bc, 'Non-generated-CSS URL changed'
            changes.append({'reference':u,'candidate':v,'sha256':ac[u]['sha256']})
        if u in ac:
            assert v in bc and ac[u]['sha256']==bc[v]['sha256'] and ac[u]['bytes']==bc[v]['bytes'], 'Generated CSS bytes differ: '+u
    return changes
