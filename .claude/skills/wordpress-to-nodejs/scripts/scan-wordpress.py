#!/usr/bin/env python3
"""Offline WordPress code scanner; produces metadata only, no source upload."""
import argparse, json, pathlib, re
p=argparse.ArgumentParser();p.add_argument('wordpress_dir');p.add_argument('--out',default='migration-scan.json');a=p.parse_args()
root=pathlib.Path(a.wordpress_dir).resolve()
if not root.is_dir():p.error('WordPress directory does not exist')
patterns={'shortcodes':re.compile(r'add_shortcode\s*\(\s*[\'\"]([^\'\"]+)'), 'rest_routes':re.compile(r'register_rest_route\s*\(\s*[\'\"]([^\'\"]+)'), 'post_types':re.compile(r'register_post_type\s*\(\s*[\'\"]([^\'\"]+)')}
results={k:[] for k in patterns}; files=0
for base in ('wp-content/themes','wp-content/plugins','wp-content/mu-plugins'):
 d=root/base
 if not d.exists():continue
 for f in d.rglob('*.php'):
  if f.is_symlink() or f.stat().st_size>2_000_000:continue
  files+=1
  s=f.read_text(encoding='utf-8',errors='replace')
  for k,pat in patterns.items():
   for m in pat.finditer(s):results[k].append({'name':m.group(1),'file':str(f.relative_to(root))})
report={'php_files_scanned':files,'findings':results,'warning':'Static analysis is incomplete. Inspect dynamic registration and runtime hooks manually.'}
pathlib.Path(a.out).write_text(json.dumps(report,indent=2),encoding='utf-8')
print('Wrote',a.out,'with',files,'PHP files scanned')
