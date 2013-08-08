function [feats, frames] = LoadO1SIFTFeature(feat_file)
	fmt_str = '%f %f %f %f %f';
	for ii = 1:128,
		fmt_str = [fmt_str ' %d'];
	end

	fid = fopen(feat_file);
	num_dim = textscan(fid, '%d', 1);
	num_desc = textscan(fid, '%d', 1);
	
	feats = textscan(fid, fmt_str);
	frames = cell2mat(feats(:,1:2));
	frames = frames';				
	feats = cell2mat(feats(:,6:133)); 	% discard first 5 parameters
	feats = feats';			% do transpose: 128 x numpoints
	fclose(fid);
end