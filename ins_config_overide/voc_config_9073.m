function conf = voc_config_9073()
	conf.pascal.year = '9073';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9073/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9073/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9073/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9073/Images/%s.txt';
end