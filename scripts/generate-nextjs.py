#!/usr/bin/env python3
"""Build a runnable Next.js public-content staging preview from WPNM JSON.
No arbitrary WordPress PHP or shortcodes are executed. Unconverted features are reported.
"""
import argparse, json, re, shutil
from pathlib import Path
from urllib.parse import urlsplit

def main():
    ap=argparse.ArgumentParser()
    ap.add_argument('--content',required=True,type=Path)
    ap.add_argument('--output',required=True,type=Path)
    a=ap.parse_args()
    data=json.loads(a.content.read_text(encoding='utf-8'))
    if data.get('schema_version')!=1 or not isinstance(data.get('items'),list):
        ap.error('Unsupported content export schema')
    root=a.output.resolve()
    if root.exists() and any(root.iterdir()):
        ap.error('Output directory must be empty')
    template=Path(__file__).resolve().parents[1]/'templates/nextjs-starter'
    shutil.copytree(template,root,dirs_exist_ok=True)
    entries=[]; warnings=[]; seen=set()
    for item in data['items']:
        url=urlsplit(item.get('url',''))
        route=url.path.strip('/')
        if route in seen:
            warnings.append('Duplicate URL path: '+route)
            continue
        seen.add(route)
        html=item.get('html','')
        if re.search(r'\[[a-zA-Z][\w-]*(?:\s[^\]]*)?\]',html):
            warnings.append('Possible unconverted shortcode: '+route)
        if re.search(r'<(?:form|script|iframe)\b',html,re.I):
            warnings.append('Review interactive/embed content: '+route)
        entries.append({'path':route,'title':str(item.get('title','')),'html':html,'source':item.get('url','')})
    (root/'content.json').write_text(json.dumps(entries,ensure_ascii=False,indent=2),encoding='utf-8')
    (root/'MIGRATION_REPORT.md').write_text('# Migration report\n\n'+
        f'Imported public routes: {len(entries)}\n\n'+
        'This is a staging content preview, NOT a complete functional WordPress conversion.\n\n'+
        '## Manual migration work\n'+''.join('- '+w+'\n' for w in warnings)+
        '\nAuthentication, plugins, forms, private content, data models and integrations must be migrated separately.\n',encoding='utf-8')
    print(f'Generated {len(entries)} routes in {root}; {len(warnings)} warnings')
if __name__=='__main__': main()
