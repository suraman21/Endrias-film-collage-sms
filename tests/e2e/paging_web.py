#!/usr/bin/env python3
"""Phase 3 paging — live API + browser checks (local test server only).

Seeds enough rows to page through (members, review packets, teachers), checks every paged endpoint keeps its OLD answer when no
`page` is sent, then drives each pager screen in Chromium. All seed rows carry
a PG marker and are removed at the end.

Usage: SSMS_AUDIT_TESTING=1 python3 -u tests/e2e/paging_web.py [base_url]
"""
import os, re, subprocess, sys, json
import requests
from playwright.sync_api import sync_playwright

av = [a for a in sys.argv[1:] if not a.startswith('-')]
BASE = (av[0] if av else 'http://127.0.0.1:8080').rstrip('/')
if 'felegekidusan' in BASE:
    sys.exit('Refusing to run against production.')
PW = 'AuditTest#2026'
SHOTS = '/home/user/SSMS/tests/e2e/shots_paging'
os.makedirs(SHOTS, exist_ok=True)
RES = []


def ck(name, cond, info=''):
    RES.append((name, bool(cond)))
    print(('PASS ' if cond else 'FAIL ') + name + (('  — ' + str(info)[:300]) if (info and not cond) else ''))


def sql(q):
    r = subprocess.run(['mysql', '-ussms', '-pssms', '-h127.0.0.1', 'ssms_test', '-N', '-B',
                        '--default-character-set=utf8mb4', '-e', q], capture_output=True, text=True)
    if r.returncode:
        print('SQL ERR', r.stderr[:300])
    return r.stdout.strip()


def session(user, page='/admin/index.php'):
    s = requests.Session()
    h = s.get(BASE + '/admin/index.php').text
    tok = re.search(r'name="csrf_token" value="([^"]+)"', h).group(1)
    s.post(BASE + '/admin/backend/login.php', data={'username': user, 'password': PW, 'csrf_token': tok}, allow_redirects=False)
    return s


def jget(s, url):
    r = s.get(BASE + url)
    try:
        return r.status_code, r.json()
    except Exception:
        return r.status_code, {'raw': r.text[:300]}


def cleanup():
    sql("DELETE FROM grade_submissions WHERE review_notes='PGSEED'")
    sql("DELETE FROM users WHERE username LIKE 'pgteach\\_%'")
    sql("DELETE FROM members WHERE member_code LIKE 'PG-%'")


# ── seed ──────────────────────────────────────────────────────────────
cleanup()
sql("DELETE FROM security_rate_limits")
subprocess.run('rm -f /tmp/ssms_ratelimit/* /home/user/SSMS/admin/uploads/cache/rate_*.json', shell=True)
vals = ','.join(f"('PG ሙከራ {i}','PGtest{i:03d}','Seed','PG-{i:04d}','active')" for i in range(1, 131))
sql(f"INSERT INTO members (full_name_am, student_name, father_name, member_code, status) VALUES {vals}")
vals = ','.join(f"(3,1,'attendance',DATE_SUB('2000-12-31', INTERVAL {i} DAY),'submitted','PGSEED')" for i in range(70))
sql(f"INSERT INTO grade_submissions (teacher_id, class_id, submission_type, attendance_date, status, review_notes) VALUES {vals}")
ph = sql("SELECT password_hash FROM users WHERE username='audit_super'")
vals = ','.join(f"('pgteach_{i:02d}','pgteach_{i:02d}@pg.local','{ph}','PG Teacher {i:02d}','teacher',{0 if i < 10 else 1})" for i in range(60))
sql(f"INSERT INTO users (username, email, password_hash, full_name, role, is_active) VALUES {vals}")

N_MEM = int(sql("SELECT COUNT(*) FROM members WHERE status='active'"))
N_SUB = int(sql("SELECT COUNT(*) FROM grade_submissions"))
N_TEACH_ACTIVE = int(sql("SELECT COUNT(*) FROM users WHERE role='teacher' AND is_active=1"))
N_TEACH_INACT = int(sql("SELECT COUNT(*) FROM users WHERE role='teacher' AND is_active=0"))
print(f'seeded: members={N_MEM} subs={N_SUB} teachers={N_TEACH_ACTIVE}+{N_TEACH_INACT}')

try:
    # ── API: paged answers + unchanged old defaults ───────────────────
    edu = session('audit_edu')
    st, d = jget(edu, '/admin/api_communication.php?action=get_submissions')
    ck('A01 submissions without page: old shape (no total)', st == 200 and 'submissions' in d and 'total' not in d, d)
    ck('A02 submissions without page: all rows (<=200)', len(d.get('submissions', [])) == min(200, N_SUB), len(d.get('submissions', [])))
    st, d = jget(edu, '/admin/api_communication.php?action=get_submissions&page=2&per_page=25')
    ck('A03 submissions page 2: 25 rows + total', st == 200 and len(d.get('submissions', [])) == 25 and d.get('total') == N_SUB, {k: d.get(k) for k in ('total', 'page', 'total_pages')})
    ck('A04 submissions page 2: page/total_pages', d.get('page') == 2 and d.get('total_pages') == -(-N_SUB // 25), d.get('total_pages'))
    st, d = jget(edu, '/admin/api_communication.php?action=get_submissions&page=999&per_page=999')
    ck('A05 bad per_page -> 50, page clamped', d.get('per_page') == 50 and d.get('page') == d.get('total_pages'), {k: d.get(k) for k in ('per_page', 'page', 'total_pages')})

    tok = requests.post(BASE + '/api/v1/auth/login', json={'username': 'audit_edu', 'password': PW, 'device_id': 'pg-test'}).json()['data']['token']
    H = {'Authorization': 'Bearer ' + tok}
    d = requests.get(BASE + '/api/v1/grades/submissions?page=1&per_page=50', headers=H).json()
    ck('A06 v1 old APK call: no total key', isinstance(d.get('data'), (dict, list)) and 'total' not in (d['data'] if isinstance(d['data'], dict) else {}), str(d)[:200])
    d = requests.get(BASE + '/api/v1/grades/submissions?with_total=1&page=2&per_page=50', headers=H).json()['data']
    ck('A07 v1 with_total: total + page 2 rows', d.get('total') == N_SUB and len(d.get('submissions', [])) == min(50, N_SUB - 50), {k: d.get(k) for k in ('total', 'page')})

    # v1 enrollment search (mobile M4): old answer unchanged; offset + total opt-in.
    d = requests.get(BASE + '/api/v1/enrollment/search?q=PGtest&limit=20', headers=H).json()
    ck('A22 v1 enrollment search old call: 20 rows, no total', len(d.get('data', {}).get('members', [])) == 20 and 'total' not in d.get('data', {}), str(d)[:200])
    d = requests.get(BASE + '/api/v1/enrollment/search?q=PGtest&limit=20&offset=20&with_total=1', headers=H).json().get('data', {})
    first20 = [m['id'] for m in requests.get(BASE + '/api/v1/enrollment/search?q=PGtest&limit=20', headers=H).json()['data']['members']]
    ck('A23 v1 enrollment search with_total + offset: total 130, next 20, no overlap', d.get('total') == 130 and len(d.get('members', [])) == 20 and not (set(first20) & {m['id'] for m in d.get('members', [])}), {k: d.get(k) for k in ('total', 'offset')})
    d = requests.get(BASE + '/api/v1/enrollment/search?q=PGtest&limit=20&offset=120&with_total=1', headers=H).json().get('data', {})
    ck('A24 v1 enrollment search last page: 10 rows', len(d.get('members', [])) == 10, len(d.get('members', [])))

    st, d = jget(edu, '/admin/api_list_members.php?view=picker&limit=50&page=2')
    ck('A11 picker page 2 + total', st == 200 and d.get('total') == N_MEM and len(d.get('members', [])) == min(50, N_MEM - 50), (d.get('total'), N_MEM, len(d.get('members', []))))

    st, d = jget(edu, '/admin/api_teachers.php?action=get_teachers')
    ck('A12 get_teachers without page: old shape, active only, <=100', st == 200 and 'teachers' in d and 'total' not in d and len(d['teachers']) == min(100, N_TEACH_ACTIVE), (len(d.get('teachers', [])), N_TEACH_ACTIVE))
    names = [t.get('full_name') or '' for t in d.get('teachers', [])]
    ck('A13 get_teachers old default sorted by name', names == sorted(names, key=lambda x: x.lower()) or len(names) < 2, names[:5])
    st, d = jget(edu, '/admin/api_teachers.php?action=get_teachers&page=2&per_page=25&include_inactive=1')
    ck('A14 get_teachers paged incl. inactive', d.get('total') == N_TEACH_ACTIVE + N_TEACH_INACT and d.get('page') == 2, {k: d.get(k) for k in ('total', 'page')})
    st, d = jget(edu, '/admin/api_teachers.php?action=get_teachers&page=1&per_page=25&include_inactive=1&only_inactive=1')
    ck('A15 get_teachers only_inactive', d.get('total') == N_TEACH_INACT and all(int(t.get('is_active', 0)) == 0 for t in d.get('teachers', [])), d.get('total'))
    st, d = jget(edu, '/admin/api_teachers.php?action=get_teachers&page=1&per_page=25&q=PG%20Teacher%2015')
    ck('A16 get_teachers search', d.get('total') == 1, d.get('total'))
    st, d = jget(edu, '/admin/api_teachers.php?action=get_teachers&page=1&per_page=25&sort=classes')
    ck('A17 get_teachers sort=classes ok', st == 200 and d.get('status') == 'success', d)

    hr = session('audit_hr')
    st, d = jget(hr, '/admin/api_guardians.php?action=list_suggestions')
    ck('A20 guardian suggestions report total', st == 200 and isinstance(d.get('total'), int) and d['total'] >= len(d.get('suggestions', [])), str(d)[:200])
    st, d = jget(sup, '/admin/api_attendance_warnings.php?action=list&limit=25&offset=0')
    ck('A21 attendance warnings list answers (limit/offset)', st == 200 and d.get('status') == 'success', str(d)[:200])

    # ── Browser ────────────────────────────────────────────────────────
    with sync_playwright() as p:
        br = p.chromium.launch()

        def open_as(user, url):
            ctx = br.new_context(viewport={'width': 1366, 'height': 900})
            page = ctx.new_page()
            errs = []
            page.on('pageerror', lambda e: errs.append(str(e)[:200]))
            page.on('dialog', lambda dl: (errs.append('native ' + dl.type), dl.dismiss()))
            page.goto(BASE + '/admin/index.php')
            page.fill('#username', user); page.fill('#password', PW)
            page.click('button[type=submit]'); page.wait_for_load_state('networkidle')
            page.goto(BASE + url); page.wait_for_load_state('networkidle')
            return ctx, page, errs

        def txt(page, sel):
            return page.evaluate("(s)=>{const e=document.querySelector(s);return e?e.innerText.replace(/\\s+/g,' ').trim():''}", sel)

        def wait_txt(page, sel, pat, ms=8000):
            try:
                page.wait_for_function("([s,p])=>{const e=document.querySelector(s);return e&&new RegExp(p).test(e.innerText.replace(/\\s+/g,' '))}", arg=[sel, pat], timeout=ms)
                return True
            except Exception:
                return False

        # B1 HR All members (PaginatedList)
        ctx, page, errs = open_as('audit_hr', '/admin/dashboards/hr-dept.php')
        page.evaluate("()=>{localStorage.removeItem('fkss_pp_membersTableBody');showSection('members');window.loadAllMembers&&loadAllMembers();return 1}")
        page.wait_for_selector('#membersTableBody tr', timeout=10000)
        page.wait_for_timeout(600)
        host = '#section-members [data-fk-pager-range]'
        ck('B01 HR members: range 1–50 of N', wait_txt(page, host, f'1[–-]50 of {N_MEM}'), txt(page, host))
        ck('B02 HR members: 50 rows', page.locator('#membersTableBody tr').count() == 50, page.locator('#membersTableBody tr').count())
        page.click('#section-members [data-fk-pager-next]')
        ck('B03 HR members: Next -> 51–100', wait_txt(page, host, f'51[–-]100 of {N_MEM}'), txt(page, host))
        first = txt(page, '#membersTableBody tr:first-child td:first-child')
        ck('B04 HR members: page 2 numbering starts at 51', first.startswith('51'), first)
        ck('B05 HR members: Page 2 of X', wait_txt(page, '#section-members [data-fk-pager-pos]', f'2 of {-(-N_MEM // 50)}'), txt(page, '#section-members [data-fk-pager-pos]'))
        page.click('#section-members [data-fk-pager-prev]')
        ck('B06 HR members: Previous -> 1–50', wait_txt(page, host, f'1[–-]50 of {N_MEM}'), txt(page, host))
        page.click('#section-members [data-fk-pager-more]')
        ck('B07 HR members: Load more -> 1–100 (100 rows)', wait_txt(page, host, f'1[–-]100 of {N_MEM}') and page.locator('#membersTableBody tr').count() == 100, txt(page, host))
        last = txt(page, '#membersTableBody tr:last-child td:first-child')
        ck('B08 HR members: appended rows numbered to 100', last.startswith('100'), last)
        page.select_option('#section-members [data-fk-pager-size]', '25')
        ck('B09 HR members: per page 25 -> 1–25', wait_txt(page, host, f'1[–-]25 of {N_MEM}') and page.locator('#membersTableBody tr').count() == 25, txt(page, host))
        page.reload(); page.wait_for_load_state('networkidle')
        page.evaluate("()=>{showSection('members');loadAllMembers();return 1}")
        page.wait_for_timeout(1200)
        ck('B10 HR members: per page remembered after reload', wait_txt(page, host, f'1[–-]25 of {N_MEM}'), txt(page, host))
        page.fill('#memberSearchInput', 'PGtest12')
        ck('B11 HR members: search narrows + total updates', wait_txt(page, host, r'of 1[01]\b'), txt(page, host))
        page.screenshot(path=SHOTS + '/hr_members.png')
        ck('B12 HR members: no JS errors', not errs, errs)
        ctx.close()

        # B2 School Admin (cursor pager, own theme)
        ctx, page, errs = open_as('audit_school', '/admin/dashboards/school_admin.php')
        page.evaluate("()=>{localStorage.removeItem('fkss_pp_school_members');nav('members');return 1}")
        page.wait_for_timeout(1500)
        rc = txt(page, '#resultCount')
        ck('B13 School Admin: shows range + total', re.search(rf'1[–-]\d+ of {N_MEM}', rc), rc)
        opts = page.evaluate("()=>[...document.querySelectorAll('#perPage option')].map(o=>o.value)")
        ck('B14 School Admin: per page 25/50/100/200', all(v in opts for v in ['25', '50', '100', '200']), opts)
        page.select_option('#perPage', '25'); page.wait_for_timeout(1200)
        ck('B15 School Admin: per page 25 -> 1–25', re.search(rf'1[–-]25 of {N_MEM}', txt(page, '#resultCount')), txt(page, '#resultCount'))
        nxt = page.locator('#pag [data-fk-pager-next]')
        if nxt.count():
            nxt.first.click(); page.wait_for_timeout(1200)
        ck('B16 School Admin: Next -> 26–50', re.search(rf'26[–-]50 of {N_MEM}', txt(page, '#resultCount')), txt(page, '#resultCount'))
        more = page.locator('#pag [data-fk-pager-more]')
        if more.count():
            more.first.click(); page.wait_for_timeout(1200)
        ck('B17 School Admin: Load more -> 26–75', re.search(rf'26[–-]75 of {N_MEM}', txt(page, '#resultCount')), txt(page, '#resultCount'))
        prv = page.locator('#pag [data-fk-pager-prev]')
        if prv.count():
            prv.first.click(); page.wait_for_timeout(1200)
        ck('B18 School Admin: Previous -> 1–25', re.search(rf'1[–-]25 of {N_MEM}', txt(page, '#resultCount')), txt(page, '#resultCount'))
        page.screenshot(path=SHOTS + '/school_admin.png')
        ck('B19 School Admin: no JS errors', not errs, errs)
        ctx.close()

        # B3 Education review inbox + teachers
        ctx, page, errs = open_as('audit_edu', '/admin/dashboards/edu_dept.php')
        page.evaluate("()=>{localStorage.removeItem('fkss_pp_edu_reviews');localStorage.removeItem('fkss_pp_edu_teachers');nav('submissions');return 1}")
        page.wait_for_timeout(800)
        # The inbox opens on Drafts; the seeded packets are Submitted.
        page.evaluate("()=>{document.getElementById('subFilterStatus').value='submitted';loadSubmissions();return 1}")
        N_SUB = int(sql("SELECT COUNT(*) FROM grade_submissions WHERE status='submitted'"))
        rng = '#submissionsPager [data-fk-pager-range]'
        ck('B20 Edu inbox: range 1–50 of N', wait_txt(page, rng, f'1[–-]50 of {N_SUB}'), txt(page, rng))
        page.select_option('#submissionsPager [data-fk-pager-size]', '25')
        ck('B21 Edu inbox: per page 25', wait_txt(page, rng, f'1[–-]25 of {N_SUB}'), txt(page, rng))
        page.click('#submissionsPager [data-fk-pager-next]')
        ck('B22 Edu inbox: Next -> 26–50', wait_txt(page, rng, f'26[–-]50 of {N_SUB}'), txt(page, rng))
        page.click('#submissionsPager [data-fk-pager-more]')
        ck('B23 Edu inbox: Load more -> 26–75 (or to the end)', wait_txt(page, rng, f'26[–-]{min(75, N_SUB)} of {N_SUB}'), txt(page, rng))
        page.screenshot(path=SHOTS + '/edu_inbox.png')
        page.evaluate("()=>{nav('teachers');return 1}")
        trng = '#teacherPager [data-fk-pager-range]'
        ck('B24 Edu teachers: paged range of active teachers', wait_txt(page, trng, f'of {N_TEACH_ACTIVE}'), txt(page, trng))
        page.click('#teacherPager [data-fk-pager-next]')
        ck('B25 Edu teachers: Next works', wait_txt(page, '#teacherPager [data-fk-pager-pos]', r'Page 2 of'), txt(page, '#teacherPager'))
        page.fill('#teacherSearch', 'PG Teacher 15')
        ck('B26 Edu teachers: search goes to server (1 result)', wait_txt(page, trng, r'of 1\b'), txt(page, trng))
        page.screenshot(path=SHOTS + '/edu_teachers.png')
        ck('B27 Edu: no JS errors', not errs, errs)
        ctx.close()

        # B6 Attendance monthly + warnings screens load with the new controls
        ctx, page, errs = open_as('audit_super', '/admin/attendance_monthly.php')
        ck('B39 Monthly attendance: per-page control present', page.locator('#am-size').count() == 1 and page.locator('#am-more').count() == 1)
        page.goto(BASE + '/admin/attendance_warnings.php'); page.wait_for_load_state('networkidle'); page.wait_for_timeout(800)
        ck('B40 Warnings: pager host present', page.locator('#aw-pager').count() == 1)
        ck('B41 Monthly + warnings: no JS errors', not errs, errs)
        page.screenshot(path=SHOTS + '/attendance_warnings.png')
        ctx.close()
        br.close()
finally:
    cleanup()

passed = sum(1 for _, ok in RES if ok)
print(f'\n{passed}/{len(RES)} passed')
sys.exit(0 if passed == len(RES) else 1)
