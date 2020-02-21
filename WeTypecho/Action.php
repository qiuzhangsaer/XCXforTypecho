<?php
header('Access-Control-Allow-Origin: *');
class WeTypecho_Action extends Typecho_Widget implements Widget_Interface_Do {
    private $db;
    private $res;
    const LACK_PARAMETER = 'Not found';
    public function __construct($request, $response, $params = NULL) {
        parent::__construct($request, $response, $params);
        $this->db  = Typecho_Db::get();
        $this->res = new Typecho_Response();
        $this->apisecret = Typecho_Widget::widget('Widget_Options')->plugin('WeTypecho')->apiSecret;
        $this->appid = Typecho_Widget::widget('Widget_Options')->plugin('WeTypecho')->appid;
        $this->appsecret = Typecho_Widget::widget('Widget_Options')->plugin('WeTypecho')->appsecret;
        $swipe = Typecho_Widget::widget('Widget_Options')->plugin('WeTypecho')->swipePosts;
        if (method_exists($this, $this->request->type)) {
            call_user_func(array(
                $this,
                $this->request->type
            ));
        } else {
            $this->defaults();
        }
    }
    private function defaults() {
        $this->export(NULL);
    }
    private function checkApisec($sec)
    {
        if(strcmp($sec,$this->apisecret) != 0) {
            $this->export('API secret error');
            }
    }

    private function posts() {
        $sec = self::GET('apisec', 'null');
        self::checkApisec($sec);
        $pageSize = (int) self::GET('pageSize', 1000);
        $page     = (int) self::GET('page', 1);
        $authorId = self::GET('authorId', 0);
        $offset   = $pageSize * ($page - 1);
        $getpage = self::GET('getpage', 0);
        $idx     = self::GET('idx',-1);

        // 根据cid偏移获取文章
        if (isset($_GET['cid'])) {
            $cid = self::GET('cid');
            if($getpage) {
                $select = $this->db->select('cid', 'title', 'created', 'type', 'slug', 'text','commentsNum')->from('table.contents')->where('type = ?', 'page')->where('status = ?', 'publish')->where('created < ?', time())->order('table.contents.created', Typecho_Db::SORT_DESC)->offset($offset)->limit($pageSize);
            } else {
                $select = $this->db->select('cid', 'title', 'created', 'type', 'slug', 'text','commentsNum')->from('table.contents')->where('type = ?', 'post')->where('status = ?', 'publish')->where('created < ?', time())->order('table.contents.created', Typecho_Db::SORT_DESC)->offset($offset)->limit($pageSize);
            }
             $select->where('cid = ?', $cid);
            //更新点击量数据库
            $row = $this->db->fetchRow($this->db->select('views')->from('table.contents')->where('cid = ?', $cid));
            $this->db->query($this->db->update('table.contents')->rows(array('views' => (int)$row['views']+1))->where('cid = ?', $cid));
        }
        else
        {
            //如果不指定具体文章CID，不抓取text
            $select   = $this->db->select('cid', 'title', 'created', 'type', 'slug','commentsNum')->from('table.contents')->where('type = ?', 'post')->where('status = ?', 'publish')->where('created < ?', time())->order('table.contents.created', Typecho_Db::SORT_DESC)->offset($offset)->limit($pageSize);
        }
        // 根据分类或标签获取文章
        if (isset($_GET['category']) || isset($_GET['tag'])) {
            $name     = isset($_GET['category']) ? $_GET['category'] : $_GET['tag'];
            $resource = $this->db->fetchAll($this->db->select('cid')->from('table.relationships')->join('table.metas', 'table.metas.mid = table.relationships.mid', Typecho_Db::LEFT_JOIN)->where('slug = ?', $name));
            $cids     = array();
            foreach ($resource as $item) {
                $cids[] = $item['cid'];
            }
            $select->where('cid IN ?', $cids);
        }
        if($idx>=0){
            switch($idx)
            {
                case 0:
                    //浏览量
                    $select->order('table.contents.views', Typecho_Db::SORT_DESC);
                    break;
                case 1:
                    //评论数
                    $select->order('table.contents.commentsNum', Typecho_Db::SORT_DESC);
                    break;
                case 2:
                    //点赞数
                    $select->order('table.contents.likes', Typecho_Db::SORT_DESC);
                    break;
                default:
                    break;
            }
        }
        $posts  = $this->db->fetchAll($select);
        $result = array();
        $temp = Typecho_Widget::widget('Widget_Options')->plugin('WeTypecho')->hiddenShare;
        foreach ($posts as $post) {
            $post        = $this->widget("Widget_Abstract_Contents")->push($post);
            $post['tag'] = $this->db->fetchAll($this->db->select('name')->from('table.metas')->join('table.relationships', 'table.metas.mid = table.relationships.mid', Typecho_DB::LEFT_JOIN)->where('table.relationships.cid = ?', $post['cid'])->where('table.metas.type = ?', 'tag'));
            $post['thumb'] = $this->db->fetchAll($this->db->select('str_value')->from('table.fields')->where('cid = ?', $post['cid']))?$this->db->fetchAll($this->db->select('str_value')->from('table.fields')->where('cid = ?', $post['cid'])):array(array("str_value"=>"https://api.isoyu.com/bing_images.php"));
            $post['views'] = $this->db->fetchAll($this->db->select('views')->from('table.contents')->where('table.contents.cid = ?', $post['cid']));
            $post['likes'] = $this->db->fetchAll($this->db->select('likes')->from('table.contents')->where('table.contents.cid = ?', $post['cid']));
            $post['showshare'] = $temp;
            $result[]    = $post;
        }
        $this->export($result);
    }
	/**
	 * 获取关于
	 */
    private function getabout()
    {
       $sec = self::GET('apisec', 'null');
       self::checkApisec($sec);
       $cid = self::GET('cid');
       $select = $this->db->select('text')->from('table.contents')->where('type = ?', 'post')->where('created < ?', time())->order('table.contents.created', Typecho_Db::SORT_DESC);
	   $select->where('cid = ?', $cid);
       $posts = $this->db->fetchAll($select);
	   //更新点击量数据库
	   $row = $this->db->fetchRow($this->db->select('views')->from('table.contents')->where('cid = ?', $cid));
	   $this->db->query($this->db->update('table.contents')->rows(array('views' => (int)$row['views']+1))->where('cid = ?', $cid));
       $this->export($posts[0]['text']);
    }

    private function monitorverfy()
    {
        $sec = self::GET('apisec', 'null');
        self::checkApisec($sec);

        $openid = self::GET('openid', 'null');
        $oid = Typecho_Widget::widget('Widget_Options')->plugin('WeTypecho')->monitorOid;
        if($openid == $oid)
            $this->export('true');
        else
            $this->export('false');
    }

    private function getcat()
    {
        $sec = self::GET('apisec', 'null');
        self::checkApisec($sec);
        $sec = self::GET('apisec', 'null');
        self::checkApisec($sec);
        $temp = Typecho_Widget::widget('Widget_Options')->plugin('WeTypecho')->hiddenmid;
        $select = $this->db->select('name','slug','type','description','mid')->from('table.metas')->where('table.metas.type = ?','category');
        $hiddenmids = explode(",",$temp);
        $hidden = false;
        if(sizeof($hiddenmids)>0 && intval($hiddenmids[0])) {
        $select->where('mid in ?', $hiddenmids);
        $hidden = true;
        }
        $cat = $this->db->fetchAll($select);
        if(!$hidden) {
        $cat_recent = $cat[0];
        $cat_recent['name'] = "最近发布";
        $cat_recent['slug'] = "最近发布";
        $cat_recent['mid'] = "99999999";
        array_unshift($cat,$cat_recent);
        }
        $this->export($cat);
    }

    //首页参数 pageSize
    private function recentPost() {
        $sec = self::GET('apisec', 'null');
        self::checkApisec($sec);
        $pageSize = self::GET('pageSize', 10);
        $this->widget("Widget_Contents_Post_Recent", "pageSize={$pageSize}")->to($post);
        $recentPost = array();
        while ($post->next()) {
            $recentPost[] = array(
                "cid" => $post->cid,
                "title" => $post->title,
                "permalink" => $post->permalink
            );
        }
        $this->export($recentPost);
    }

    private function get_stat() {
        $sec = self::GET('apisec', 'null');
        self::checkApisec($sec);

        // Memory
        if (false === ($str = @file("/proc/meminfo")))  return false;
        $str = implode("", $str);

        preg_match_all("/MemTotal\s{0,}\:+\s{0,}([\d\.]+).+?MemFree\s{0,}\:+\s{0,}([\d\.]+).+?Cached\s{0,}\:+\s{0,}([\d\.]+).+?SwapTotal\s{0,}\:+\s{0,}([\d\.]+).+?SwapFree\s{0,}\:+\s{0,}([\d\.]+)/s", $str, $buf);
        preg_match_all("/Buffers\s{0,}\:+\s{0,}([\d\.]+)/s", $str, $buffers);


        $res['memTotal'] = round($buf[1][0]/1024, 2);

        $res['memFree'] = round($buf[2][0]/1024, 2);

        $res['memBuffers'] = round($buffers[1][0]/1024, 2);
        $res['memCached'] = round($buf[3][0]/1024, 2);

        $res['memUsed'] = $res['memTotal']-$res['memFree'];

        $res['memPercent'] = (floatval($res['memTotal'])!=0)?round($res['memUsed']/$res['memTotal']*100,2):0;


        $res['memRealUsed'] = $res['memTotal'] - $res['memFree'] - $res['memCached'] - $res['memBuffers']; //真实内存使用
        $res['memRealFree'] = $res['memTotal'] - $res['memRealUsed']; //真实空闲
        $res['memRealPercent'] = (floatval($res['memTotal'])!=0)?round($res['memRealUsed']/$res['memTotal']*100,2):0; //真实内存使用率

        $res['memCachedPercent'] = (floatval($res['memCached'])!=0)?round($res['memCached']/$res['memTotal']*100,2):0; //Cached内存使用率

        $res['swapTotal'] = round($buf[4][0]/1024, 2);

        $res['swapFree'] = round($buf[5][0]/1024, 2);

        $res['swapUsed'] = round($res['swapTotal']-$res['swapFree'], 2);

        $res['swapPercent'] = (floatval($res['swapTotal'])!=0)?round($res['swapUsed']/$res['swapTotal']*100,2):0;
        if (false == ($str = @file("/proc/loadavg"))) return false;

        $str = explode(" ", implode("", $str));

        $str = array_chunk($str, 4);

        $res['loadAvg'] = implode(" ", $str[0]);
        //磁盘
        $res['DiskTotal'] = round(@disk_total_space(".")/(1024*1024*1024),3); //总
        $res['DiskFree'] = round(@disk_free_space(".")/(1024*1024*1024),3); //可用

        //网卡
        $strs = @file("/proc/net/dev");

        for ($i = 2; $i < count($strs); $i++ )
        {
            preg_match_all( "/([^\s]+):[\s]{0,}(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/", $strs[$i], $info );
            if($info[1][0] == 'eth0') {
            $res['NetOut'] = $info[10][0];
            $res['NetInput'] = $info[2][0];
            }
        }
        //CPU
        if (false === ($str = @file("/proc/cpuinfo"))) return false;

        $str = implode("", $str);

        @preg_match_all("/model\s+name\s{0,}\:+\s{0,}([\w\s\)\(\@.-]+)([\r\n]+)/s", $str, $model);

        @preg_match_all("/cpu\s+MHz\s{0,}\:+\s{0,}([\d\.]+)[\r\n]+/", $str, $mhz);

        @preg_match_all("/cache\s+size\s{0,}\:+\s{0,}([\d\.]+\s{0,}[A-Z]+[\r\n]+)/", $str, $cache);

        @preg_match_all("/bogomips\s{0,}\:+\s{0,}([\d\.]+)[\r\n]+/", $str, $bogomips);

        if (false !== is_array($model[1]))
        {

            $res['cpu']['num'] = sizeof($model[1]);
            if($res['cpu']['num']==1)
                $x1 = '';
            else
                $x1 = ' ×'.$res['cpu']['num'];
            $mhz[1][0] = $mhz[1][0];
            $cache[1][0] = $cache[1][0];
            $bogomips[1][0] = $bogomips[1][0];
            $res['cpu']['model'] = $model[1][0].$mhz[1][0].$cache[1][0].$bogomips[1][0].$x1;
            $res['cpu']['mhz'] = $mhz[1][0];
            $res['cpu']['cache'] = $cache[1][0];
            $res['cpu']['bogomips'] = $bogomips[1][0];
        }
        $this->export($res);
    }

    function http_post_data($url, $data_string) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($data_string))
        );
        ob_start();
        curl_exec($ch);
        $return_content = ob_get_contents();
		//echo $return_content."<br>";
        ob_end_clean();

        $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      //  return array($return_code, $return_content);
	  return  $return_content;
    }
  
    private function getpostbymid()
    {
        $sec = self::GET('apisec', 'null');
        self::checkApisec($sec);
        $pageSize = (int) self::GET('pageSize', 1000);
        $except = (int) self::GET('except', 'null');
        $mid = self::GET('mid', -1);
        $select = [];
        if($mid == 99999999) {
            $posts = $this->db->fetchAll($this->db->select('cid', 'title', 'created', 'type', 'slug','commentsNum')->from('table.contents')->where('type = ?', 'post')->where('status = ?', 'publish')->where('created < ?', time())->order('table.contents.created', Typecho_Db::SORT_DESC)->limit(10));
            foreach($posts as $post) {
                $temp = $this->db->fetchAll($this->db->select('cid', 'title', 'created','commentsNum', 'views', 'likes')->from('table.contents')->where('cid = ?', $post['cid'])->where('status = ?', 'publish'));
                if(sizeof($temp)>0) {
                    $temp['0']['thumb'] = $this->db->fetchAll($this->db->select('str_value')->from('table.fields')->where('cid = ?', $post['cid']));
                    array_push($select,$temp[0]);
                }
            }
            if(sizeof($posts)>0) {
                $this->export($select);
            } else {
                $this->export(null);
            }
        }
        else if($mid>=0)
        {
            $categoryListWidget = $this->widget('Widget_Metas_Category_List', 'current='.$mid);
            $children = $categoryListWidget->getAllChildren($mid);
            $children[] = $mid;
            $limit = 0;
            if($except != 'null') {
                $posts = $this->db->fetchAll($this->db->select('cid','mid')->from('table.relationships')->where('mid IN ?', $children)->where('cid != ?', $except));
            } else {
                $posts = $this->db->fetchAll($this->db->select('cid','mid')->from('table.relationships')->where('mid IN ?', $children));
            }
            foreach($posts as $post) {
                $temp = $this->db->fetchAll($this->db->select('cid', 'title', 'created','commentsNum', 'views', 'likes')->from('table.contents')->where('cid = ?', $post['cid'])->where('status = ?', 'publish'));
                if(sizeof($temp)>0) {
                    $temp['0']['thumb'] = $this->db->fetchAll($this->db->select('str_value')->from('table.fields')->where('cid = ?', $post['cid']));
                    array_unshift($select,$temp[0]);
                }
                $limit++;
            }
            $overflow = sizeof($select) - $pageSize;
            for($cnt = 0; $cnt < $overflow; $cnt++) {
                array_pop($select);
            }
            if(sizeof($posts)>0) {
                $this->export($select);
            } else {
                $this->export(null);
            }
        }
        $this->export(null);
    }
  
    private function search()
    {
        $keyword = self::GET('keyword', 'null');
        if($keyword != 'null')
        {
            $cids = $this->db->fetchAll($this->db->select('cid')->from('table.contents')->where('text LIKE ?', '%' . $keyword . '%'));
            if(sizeof($cids)>0){
                foreach($cids as $cid) {
                    $post = $this->db->fetchAll($this->db->select('cid', 'title', 'created', 'type', 'slug','commentsNum')->from('table.contents')->where('cid = ?', $cid)->where('type = ?', 'post')->where('status = ?', 'publish')->where('created < ?', time()));
                    if(sizeof($post)>0 && $post[0]!=null) {
                        $post[0]        = $this->widget("Widget_Abstract_Contents")->push($post[0]);
                        $post[0]['tag'] = $this->db->fetchAll($this->db->select('name')->from('table.metas')->join('table.relationships', 'table.metas.mid = table.relationships.mid', Typecho_DB::LEFT_JOIN)->where('table.relationships.cid = ?', $cid)->where('table.metas.type = ?', 'tag'));
                        $post[0]['thumb'] = $this->db->fetchAll($this->db->select('str_value')->from('table.fields')->where('cid = ?', $cid))?$this->db->fetchAll($this->db->select('str_value')->from('table.fields')->where('cid = ?', $cid)):array(array("str_value"=>"https://api.isoyu.com/bing_images.php"));
                        $post[0]['views'] = $this->db->fetchAll($this->db->select('views')->from('table.contents')->where('table.contents.cid = ?', $cid));
                        $post[0]['likes'] = $this->db->fetchAll($this->db->select('likes')->from('table.contents')->where('table.contents.cid = ?', $cid));
                        $result[]    = $post[0];
                    }
                }
                if(sizeof($result)>0) {
                    $this->export($result);
                } else {
                    $this->export("none");
                }
            } else {
                $this->export("none");
            }
        }
        else
        {
            $this->export(null);
        }
    }
  
    private function getswiperpost()
    {
        $sec = self::GET('apisec', 'null');
        self::checkApisec($sec);

        $swipe = Typecho_Widget::widget('Widget_Options')->plugin('WeTypecho')->swipePosts;
        $cids = explode(",",$swipe);
        $result = array();
        if(sizeof($cids)>0){
            foreach($cids as $cid) {
                $post = $this->db->fetchAll($this->db->select('cid', 'title', 'created', 'type', 'slug','commentsNum')->from('table.contents')->where('cid = ?', $cid)->where('status = ?', 'publish')->where('type = ?', 'post')->where('created < ?', time()));
                if(sizeof($post)>0 && $post[0]!=null) {
                    $post[0]        = $this->widget("Widget_Abstract_Contents")->push($post[0]);
                    $post[0]['tag'] = $this->db->fetchAll($this->db->select('name')->from('table.metas')->join('table.relationships', 'table.metas.mid = table.relationships.mid', Typecho_DB::LEFT_JOIN)->where('table.relationships.cid = ?', $cid)->where('table.metas.type = ?', 'tag'));
                    $post[0]['thumb'] = $this->db->fetchAll($this->db->select('str_value')->from('table.fields')->where('cid = ?', $cid))?$this->db->fetchAll($this->db->select('str_value')->from('table.fields')->where('cid = ?', $cid)):array(array("str_value"=>"https://api.isoyu.com/bing_images.php"));
                    $post[0]['views'] = $this->db->fetchAll($this->db->select('views')->from('table.contents')->where('table.contents.cid = ?', $cid));
                    $post[0]['likes'] = $this->db->fetchAll($this->db->select('likes')->from('table.contents')->where('table.contents.cid = ?', $cid));
                    $result[]    = $post[0];
                }
            }
            if(sizeof($result)>0) {
                $this->export($result);
            } else {
                $this->export(null);
            }
        } else {
            $this->export(null);
        }
    }

    private function getcomment()
    {
        $sec = self::GET('apisec', 'null');
        self::checkApisec($sec);
        $cid = self::GET('cid', -1);
        $comments = $this->db->fetchAll($this->db->select('cid','coid','created', 'author', 'text', 'parent', 'authorImg')->from('table.comments')->where('cid = ?', $cid)->where('status = ?', 'approved')->order('table.comments.created', Typecho_Db::SORT_DESC));
        $result = array();
        //获取根评论
        foreach ($comments as $comment) {
            if($comment['parent'] == 0) {
                $result[] = $comment;
            }
        }
        //获取子评论
        foreach($comments as $comment) {
            if($comment['parent'] != 0) {
                $parent = $comment['parent'];
                $temp = $this->db->fetchAll($this->db->select('cid','coid','created', 'author', 'text', 'parent', 'authorImg')->from('table.comments')->where('cid = ?', $cid)->where('coid = ?', $parent)->where('status = ?', 'approved')->order('table.comments.created', Typecho_Db::SORT_DESC));
                if(sizeof($temp)>0)
                {
                    while($temp[0]['parent']!=0)
                    {
                        $parent = $temp[0]['parent'];
                        $temp = $this->db->fetchAll($this->db->select('cid','coid','created', 'author', 'text', 'parent', 'authorImg')->from('table.comments')->where('cid = ?', $cid)->where('coid = ?', $parent)->where('status = ?', 'approved')->order('table.comments.created', Typecho_Db::SORT_DESC));
                    }
                    for($i=0; $i<sizeof($result);$i++)
                    {
                        if($result[$i]['coid'] == $temp[0]['coid']) {
                            $comment['parentitem'] = $this->db->fetchAll($this->db->select('cid','coid','created', 'author', 'text', 'parent', 'authorImg')->from('table.comments')->where('cid = ?', $cid)->where('coid = ?', $comment['parent'])->where('status = ?', 'approved')->order('table.comments.created', Typecho_Db::SORT_DESC));
                            $result[$i]['replays'][] = $comment;
                        }
                    }
                }
            }
        }
        $this->export($result);
    }
    public function export($data = array(), $status = 200) {
        $this->res->throwJson(array(
            'status' => $status,
            'data' => $data
        ));
        exit;
    }
    public static function GET($key, $default = '') {
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }
    public function action() {
        $this->on($this->request);
    }
}
