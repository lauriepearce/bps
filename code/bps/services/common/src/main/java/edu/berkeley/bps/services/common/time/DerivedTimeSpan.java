package edu.berkeley.bps.services.common.time;

import java.util.ArrayList;

//Rewrite to reference the EBTS, using that as the initial date. Requires that we
//keep all other dates (or their weights and weighted points) so we can recompute as
//the referenced TS shifts.

public class DerivedTimeSpan extends EvidenceBasedTimeSpan {
	protected TimeSpan baseTimeSpan = null;
	private long offset = 0;
	double totalAddedWeight = 0;
	long addedDatesCenterPoint = 0;

	public DerivedTimeSpan(TimeSpan baseSpan, long offset, double stdDev, double window) {
		super(baseSpan.getCenterPoint()+offset, stdDev, window);
		this.offset = offset;
		baseTimeSpan = baseSpan;
	}

	/* (non-Javadoc)
	 * @see bps.services.common.main.InferredTimeSpan#isValid()
	 */
	public boolean isValid() {
		return (baseTimeSpan != null) && baseTimeSpan.isValid();
	}

	public long getCenterPoint() {
		if(totalAddedWeight==0)
			return baseTimeSpan.getCenterPoint()+offset;
		double temp = baseTimeSpan.getCenterPoint()+offset;
		temp += addedDatesCenterPoint*totalAddedWeight;
		return Math.round(temp/(totalAddedWeight+1));
	}

	public TimeSpan getBaseTimeSpan() {
		return baseTimeSpan;
	}

	public void setBaseTimeSpan(TimeSpan baseTimeSpan) {
		this.baseTimeSpan = baseTimeSpan;
	}

	public void addDate(long newDate, double weight) {
		if(times==null) {
			times = new ArrayList<Long>();
			weights = new ArrayList<Double>();
			times.add(newDate);
			weights.add(weight);
			totalAddedWeight = weight;
			addedDatesCenterPoint = newDate;
		} else {
			times.add(newDate);
			weights.add(weight);
			double temp = addedDatesCenterPoint*totalAddedWeight + newDate*weight;
			totalAddedWeight += weight;
			addedDatesCenterPoint = Math.round(temp/totalAddedWeight);
		}
	}

}
