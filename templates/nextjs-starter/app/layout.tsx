import type { ReactNode } from 'react';
export const metadata = { title: 'Node Migration Staging', robots: { index: false, follow: false } };
export default function Layout({children}:{children:ReactNode}) {return <html lang="en"><body style={{fontFamily:'system-ui',padding:'3rem'}}>{children}</body></html>}
