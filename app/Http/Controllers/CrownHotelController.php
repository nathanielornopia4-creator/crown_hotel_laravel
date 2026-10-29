<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CrownHotelController extends Controller
{
    private function json(array $data, int $status=200) { return response()->json($data, $status); }
    private function user(Request $r): ?array {
        if (!$r->session()->has('user_id')) return null;
        return ['user_id'=>(int)$r->session()->get('user_id'), 'role'=>$r->session()->get('role','customer')];
    }
    private function loginRequired(Request $r) {
        $u=$this->user($r);
        return $u ?: $this->json(['success'=>false,'message'=>'Please log in first.'],401);
    }
    private function adminRequired(Request $r) {
        $u=$this->user($r);
        if (!$u) return $this->json(['success'=>false,'message'=>'Please log in first.'],401);
        if ($u['role']==='customer') return $this->json(['success'=>false,'message'=>'Admin access is required.'],403);
        return $u;
    }
    private function body(Request $r): array { return $r->all(); }

    public function auth(Request $r) {
        $action=$r->query('action','');
        if ($action==='me') return $this->json([
            'success'=>true, 'csrf'=>$r->session()->token(),
            'user'=>$r->session()->has('user_id') ? [
                'user_id'=>(int)$r->session()->get('user_id'),
                'full_name'=>$r->session()->get('full_name',''),
                'username'=>$r->session()->get('username',''),
                'role'=>$r->session()->get('role','customer')
            ] : null
        ]);
        $d=$this->body($r);
        try {
            if ($action==='register') {
                $v=Validator::make($d,[
                    'full_name'=>'required|string|max:150','phone'=>'required|string|max:30',
                    'email'=>'required|email|max:150','username'=>'required|string|max:80|unique:users,username',
                    'password'=>'required|string|min:6'
                ]);
                if ($v->fails()) return $this->json(['success'=>false,'message'=>$v->errors()->first()],422);
                if (DB::table('users')->where('email',$d['email'])->exists())
                    return $this->json(['success'=>false,'message'=>'That username or email is already registered.'],409);
                DB::table('users')->insert([
                    'full_name'=>trim($d['full_name']),'phone'=>trim($d['phone']),'email'=>trim($d['email']),
                    'username'=>trim($d['username']),'password_hash'=>Hash::make($d['password']),'role'=>'customer'
                ]);
                return $this->json(['success'=>true,'message'=>'Registration successful. You can now log in.']);
            }
            if ($action==='login') {
                $username=trim((string)($d['username']??'')); $password=(string)($d['password']??'');
                $adminUsername='admin@crownhotel.com'; $adminPassword='crownhotel2026';
                if (strcasecmp($username,$adminUsername)===0 && hash_equals($adminPassword,$password)) {
                    DB::table('users')->updateOrInsert(['username'=>$adminUsername],[
                        'full_name'=>'Crown Hotel Admin','phone'=>'Not provided','email'=>$adminUsername,
                        'password_hash'=>Hash::make($adminPassword),'role'=>'Hotel Administrator/Manager'
                    ]);
                }
                $u=DB::table('users')->where('username',$username)->orWhere('email',$username)->first();
                if (!$u || !Hash::check($password,$u->password_hash))
                    return $this->json(['success'=>false,'message'=>'Incorrect username or password.'],401);
                $r->session()->regenerate();
                $r->session()->put(['user_id'=>(int)$u->user_id,'full_name'=>$u->full_name,'username'=>$u->username,'role'=>$u->role]);
                return $this->json(['success'=>true,'message'=>'Login successful.','role'=>$u->role]);
            }
            if ($action==='logout') {
                $r->session()->invalidate(); $r->session()->regenerateToken();
                return $this->json(['success'=>true,'message'=>'Logged out.']);
            }
            return $this->json(['success'=>false,'message'=>'Unknown action.'],404);
        } catch (Throwable $e) {
            return $this->json(['success'=>false,'message'=>'Database error. Check your database setup.'],500);
        }
    }

    public function rooms(Request $r) {
        if ($r->isMethod('get')) {
            $rows=DB::table('rooms')->select('room_id','room_number','room_type','image','capacity','rate','status')->orderBy('room_id')->get();
            return $this->json(['success'=>true,'data'=>$rows]);
        }
        $admin=$this->adminRequired($r); if (!is_array($admin)) return $admin;
        $d=$this->body($r);
        $types=['Standard Room','Deluxe Room','Family Size','Suite'];
        $statuses=['Available','Occupied','Reserved','Maintenance','Cleaning','Inactive'];
        try {
            if ($r->isMethod('post')) {
                $id=(int)($d['room_id']??0);
                $data=[
                    'room_number'=>trim((string)($d['room_number']??'')),
                    'room_type'=>$d['room_type']??'Standard Room',
                    'capacity'=>(int)($d['capacity']??2),'rate'=>(float)($d['rate']??0),
                    'status'=>$d['status']??'Available'
                ];
                if ($data['room_number']==='' || !in_array($data['room_type'],$types,true) || $data['capacity']<1 || $data['rate']<=0 || !in_array($data['status'],$statuses,true))
                    return $this->json(['success'=>false,'message'=>'Enter valid room details.'],422);
                if ($r->hasFile('image_file')) {
                    $file=$r->file('image_file');
                    if (!$file->isValid() || $file->getSize()>5*1024*1024 || !in_array(strtolower($file->getClientOriginalExtension()),['jpg','jpeg','png','webp']))
                        return $this->json(['success'=>false,'message'=>'Only JPG, PNG, and WEBP images up to 5 MB are allowed.'],422);
                    $mime=$file->getMimeType();
                    if (!in_array($mime,['image/jpeg','image/png','image/webp'])) return $this->json(['success'=>false,'message'=>'Please upload a valid image file.'],422);
                    $name='room_'.bin2hex(random_bytes(12)).'.'.($mime==='image/jpeg'?'jpg':($mime==='image/png'?'png':'webp'));
                    $file->move(public_path('uploads'),$name); $data['image']=$name;
                }
                if ($id) {
                    if (!DB::table('rooms')->where('room_id',$id)->exists()) return $this->json(['success'=>false,'message'=>'Room not found.'],404);
                    DB::table('rooms')->where('room_id',$id)->update($data);
                    return $this->json(['success'=>true,'message'=>'Room details updated.']);
                }
                $data['image']=$data['image']??''; DB::table('rooms')->insert($data);
                return $this->json(['success'=>true,'message'=>'Room added.']);
            }
            if ($r->isMethod('patch')) {
                $id=(int)($d['room_id']??0);
                if (!$id) return $this->json(['success'=>false,'message'=>'Invalid room ID.'],422);
                if (isset($d['room_number'])) {
                    $data=['room_number'=>trim($d['room_number']),'room_type'=>$d['room_type']??'',
                        'capacity'=>(int)($d['capacity']??0),'rate'=>(float)($d['rate']??0),'status'=>$d['status']??''];
                    if ($data['room_number']==='' || !in_array($data['room_type'],$types,true) || $data['capacity']<1 || $data['rate']<=0 || !in_array($data['status'],$statuses,true))
                        return $this->json(['success'=>false,'message'=>'Enter valid room details.'],422);
                    if ($r->hasFile('image_file')) {
                        $file=$r->file('image_file');
                        if ($file->getSize()>5*1024*1024 || !in_array($file->getMimeType(),['image/jpeg','image/png','image/webp'])) return $this->json(['success'=>false,'message'=>'Only JPG, PNG, and WEBP images up to 5 MB are allowed.'],422);
                        $name='room_'.bin2hex(random_bytes(12)).'.'.($file->getMimeType()==='image/jpeg'?'jpg':($file->getMimeType()==='image/png'?'png':'webp'));
                        $file->move(public_path('uploads'),$name); $data['image']=$name;
                    }
                    DB::table('rooms')->where('room_id',$id)->update($data);
                    return $this->json(['success'=>true,'message'=>'Room details updated.']);
                }
                if (!in_array($d['status']??'', $statuses,true)) return $this->json(['success'=>false,'message'=>'Invalid room status.'],422);
                DB::table('rooms')->where('room_id',$id)->update(['status'=>$d['status']]);
                return $this->json(['success'=>true,'message'=>'Room status updated.']);
            }
        } catch (Throwable $e) {
            return $this->json(['success'=>false,'message'=>'Could not save room. Check for a duplicate room number.'],400);
        }
        return $this->json(['success'=>false,'message'=>'Method not allowed.'],405);
    }

    public function overview(Request $r) {
        $admin=$this->adminRequired($r); if (!is_array($admin)) return $admin;
        $stats=[
            'total_rooms'=>(int)DB::table('rooms')->count(),
            'available_rooms'=>(int)DB::table('rooms')->where('status','Available')->count(),
            'occupied_rooms'=>(int)DB::table('rooms')->where('status','Occupied')->count(),
            'reserved_rooms'=>(int)DB::table('rooms')->where('status','Reserved')->count(),
            'today_checkins'=>(int)DB::table('reservations')->whereDate('check_in',today())->whereIn('status',['Reserved','Checked In'])->count(),
            'today_checkouts'=>(int)DB::table('reservations')->whereDate('check_out',today())->whereIn('status',['Reserved','Checked In'])->count(),
            'today_revenue'=>(float)DB::table('reservations')->whereDate('created_at',today())->where('status','<>','Cancelled')->sum('downpayment')
        ];
        return $this->json(['success'=>true,'data'=>$stats]);
    }

    public function reservations(Request $r) {
        $u=$this->user($r);
        if ($r->isMethod('get')) {
            if (!$u) return $this->json(['success'=>false,'message'=>'Please log in first.'],401);
            $q=DB::table('reservations as r')->join('rooms as rm','rm.room_id','=','r.room_id')
                ->select('r.*','rm.room_number','rm.room_type')->orderByDesc('r.created_at');
            if ($u['role']==='customer') $q->where('r.user_id',$u['user_id']);
            return $this->json(['success'=>true,'data'=>$q->get()]);
        }
        if (!$u) return $this->json(['success'=>false,'message'=>'Please log in first.'],401);
        $d=$this->body($r);
        try {
            if ($r->isMethod('post')) {
                $room=(int)($d['room_id']??0); $in=$d['check_in']??''; $out=$d['check_out']??'';
                $name=trim((string)($d['guest_name']??'')); $phone=trim((string)($d['phone']??'')); $email=trim((string)($d['email']??''));
                $method=($d['payment_method']??'Bank Transfer')==='Walk-in'?'Walk-in':'Bank Transfer';
                $payref=trim((string)($d['payment_reference']??'')); $accepted=(int)($d['policy_accepted']??0);
                if (!$room || !$in || !$out || strtotime($out)<=strtotime($in) || !$name || !$phone || !filter_var($email,FILTER_VALIDATE_EMAIL) || !$accepted)
                    return $this->json(['success'=>false,'message'=>'Complete the guest details, dates, and accept the non-refundable policy. Check-out must be after check-in.'],422);
                $nights=(int)((strtotime($out)-strtotime($in))/86400);
                $ref='CRH-'.date('ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
                $result=DB::transaction(function() use($room,$in,$out,$name,$phone,$email,$method,$payref,$accepted,$u,$nights,$ref) {
                    $rm=DB::table('rooms')->where('room_id',$room)->lockForUpdate()->first();
                    if (!$rm || $rm->status!=='Available') throw new \RuntimeException('ROOM_UNAVAILABLE');
                    $over=DB::table('reservations')->where('room_id',$room)->whereIn('status',['Pending','Reserved','Checked In'])
                        ->where('check_out','>',$in)->where('check_in','<',$out)->exists();
                    if ($over) throw new \RuntimeException('ROOM_OVERLAP');
                    $rate=(float)$rm->rate; $total=$rate*$nights; $down=round($total*0.5,2);
                    $rid=DB::table('reservations')->insertGetId([
                        'reference_no'=>$ref,'user_id'=>$u['user_id'],'room_id'=>$room,'guest_name'=>$name,'phone'=>$phone,'email'=>$email,
                        'check_in'=>$in,'check_out'=>$out,'nights'=>$nights,'rate'=>$rate,'total'=>$total,'downpayment'=>$down,
                        'payment_method'=>$method,'payment_reference'=>$payref,'status'=>'Pending','policy_accepted'=>$accepted
                    ]);
                    DB::table('transactions')->insert(['reservation_id'=>$rid,'amount'=>$down,'method'=>$method,'reference_details'=>$payref,'status'=>'Pending']);
                    return $ref;
                });
                return $this->json(['success'=>true,'message'=>'Reservation submitted and is pending admin confirmation.','reference_no'=>$result]);
            }
            if ($r->isMethod('patch')) {
                $admin=$this->adminRequired($r); if (!is_array($admin)) return $admin;
                $id=(int)($d['reservation_id']??0); $status=$d['status']??'';
                $valid=['Pending','Reserved','Confirmed','Checked In','Checked Out','Cancelled'];
                if (!$id || !in_array($status,$valid,true)) return $this->json(['success'=>false,'message'=>'Invalid status.'],422);
                DB::transaction(function() use($id,$status) {
                    DB::table('reservations')->where('reservation_id',$id)->update(['status'=>$status]);
                    $rid=DB::table('reservations')->where('reservation_id',$id)->value('room_id');
                    if (in_array($status,['Reserved','Confirmed'],true)) DB::table('rooms')->where('room_id',$rid)->where('status','Available')->update(['status'=>'Reserved']);
                    if ($status==='Checked In') DB::table('rooms')->where('room_id',$rid)->update(['status'=>'Occupied']);
                    if (in_array($status,['Checked Out','Cancelled'],true)) DB::table('rooms')->where('room_id',$rid)->whereIn('status',['Reserved','Occupied'])->update(['status'=>'Available']);
                });
                return $this->json(['success'=>true,'message'=>'Reservation status updated.']);
            }
        } catch (Throwable $e) {
            if ($e instanceof \RuntimeException && $e->getMessage()==='ROOM_UNAVAILABLE') return $this->json(['success'=>false,'message'=>'This room is not available for reservation.'],409);
            if ($e instanceof \RuntimeException && $e->getMessage()==='ROOM_OVERLAP') return $this->json(['success'=>false,'message'=>'This room already has a reservation for the selected dates.'],409);
            return $this->json(['success'=>false,'message'=>'Could not process reservation. Please verify the database and submitted details.'],500);
        }
        return $this->json(['success'=>false,'message'=>'Method not allowed.'],405);
    }

    public function transactions(Request $r) {
        $admin=$this->adminRequired($r); if (!is_array($admin)) return $admin;
        if ($r->isMethod('get')) {
            $rows=DB::table('transactions as t')->join('reservations as r','r.reservation_id','=','t.reservation_id')
                ->select('t.*','r.reference_no','r.guest_name')->orderByDesc('t.created_at')->get();
            return $this->json(['success'=>true,'data'=>$rows]);
        }
        $d=$this->body($r); $id=(int)($d['transaction_id']??0); $status=$d['status']??'';
        if (!$id || !in_array($status,['Pending','Verified','Rejected'],true)) return $this->json(['success'=>false,'message'=>'Invalid transaction details.'],422);
        DB::table('transactions')->where('transaction_id',$id)->update(['status'=>$status]);
        return $this->json(['success'=>true,'message'=>'Transaction updated.']);
    }
}
