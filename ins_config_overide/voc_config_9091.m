function conf = voc_config_9091()
	conf.pascal.year = '9091';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9091/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9091/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9091/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9091/Images/%s.txt';
end